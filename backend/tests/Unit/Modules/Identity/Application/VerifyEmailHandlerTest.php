<?php

declare(strict_types=1);

use App\Modules\Identity\Application\Command\VerifyEmail\VerifyEmailCommand;
use App\Modules\Identity\Application\Command\VerifyEmail\VerifyEmailHandler;
use App\Modules\Identity\Domain\Entity\User;
use App\Modules\Identity\Domain\Event\UserEmailVerified;
use App\Modules\Identity\Domain\Repository\UserRepositoryInterface;
use App\Modules\Identity\Domain\ValueObject\Email;
use App\Modules\Identity\Domain\ValueObject\FullName;
use App\Modules\Identity\Domain\ValueObject\HashedPassword;
use App\Modules\Identity\Domain\ValueObject\UserId;
use Tests\Unit\Modules\Identity\Application\Support\InMemoryPasswordHasher;
use Tests\Unit\Modules\Identity\Application\Support\RecordingEventDispatcher;

it('verifies email and dispatches UserEmailVerified', function (): void {
    $users = Mockery::mock(UserRepositoryInterface::class);
    $dispatcher = new RecordingEventDispatcher;
    $hasher = new InMemoryPasswordHasher;

    $userId = UserId::generate();
    $user = User::register($userId, new Email('test@example.com'), HashedPassword::fromPlaintext('secret', $hasher), new FullName('A', 'B', null));
    $user->pullDomainEvents(); // очищаем UserRegistered

    $users->shouldReceive('findById')->once()->andReturn($user);
    $users->shouldReceive('save')->once()->with(Mockery::type(User::class));

    $handler = new VerifyEmailHandler($users, $dispatcher);
    $handler->handle(new VerifyEmailCommand((string) $userId));

    expect($dispatcher->events)->toHaveCount(1);
    expect($dispatcher->events[0])->toBeInstanceOf(UserEmailVerified::class);
});

it('is idempotent — second verify does not dispatch event', function (): void {
    $users = Mockery::mock(UserRepositoryInterface::class);
    $dispatcher = new RecordingEventDispatcher;
    $hasher = new InMemoryPasswordHasher;

    $userId = UserId::generate();
    $user = User::register($userId, new Email('test@example.com'), HashedPassword::fromPlaintext('secret', $hasher), new FullName('A', 'B', null));
    $user->pullDomainEvents();
    $user->verifyEmail(); // первая верификация

    $users->shouldReceive('findById')->once()->andReturn($user);
    $users->shouldReceive('save')->once();

    $handler = new VerifyEmailHandler($users, $dispatcher);
    // события от первого verifyEmail нужно сбросить до передачи в handler
    $user->pullDomainEvents();
    $handler->handle(new VerifyEmailCommand((string) $userId));

    expect($dispatcher->events)->toHaveCount(0);
});

it('throws RuntimeException when user not found', function (): void {
    $users = Mockery::mock(UserRepositoryInterface::class);

    $users->shouldReceive('findById')->once()->andReturnNull();

    $handler = new VerifyEmailHandler($users, new RecordingEventDispatcher);
    $handler->handle(new VerifyEmailCommand(UserId::generate()->toString()));
})->throws(RuntimeException::class);
