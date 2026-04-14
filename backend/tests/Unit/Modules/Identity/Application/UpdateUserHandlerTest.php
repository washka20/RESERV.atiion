<?php

declare(strict_types=1);

use App\Modules\Identity\Application\Command\UpdateUser\UpdateUserCommand;
use App\Modules\Identity\Application\Command\UpdateUser\UpdateUserHandler;
use App\Modules\Identity\Domain\Entity\User;
use App\Modules\Identity\Domain\Exception\DuplicateEmailException;
use App\Modules\Identity\Domain\Repository\UserRepositoryInterface;
use App\Modules\Identity\Domain\ValueObject\Email;
use App\Modules\Identity\Domain\ValueObject\FullName;
use App\Modules\Identity\Domain\ValueObject\HashedPassword;
use App\Modules\Identity\Domain\ValueObject\UserId;
use Tests\Unit\Modules\Identity\Application\Support\InMemoryPasswordHasher;
use Tests\Unit\Modules\Identity\Application\Support\RecordingEventDispatcher;

function makeStoredUser(): User
{
    $hasher = new InMemoryPasswordHasher;

    return User::register(
        UserId::generate(),
        new Email('old@example.com'),
        HashedPassword::fromPlaintext('secret', $hasher),
        new FullName('John', 'Doe', null),
    );
}

it('обновляет email если он свободен', function (): void {
    $users = Mockery::mock(UserRepositoryInterface::class);
    $dispatcher = new RecordingEventDispatcher;

    $user = makeStoredUser();
    $user->pullDomainEvents();

    $users->shouldReceive('findById')->once()->andReturn($user);
    $users->shouldReceive('existsByEmail')->once()->andReturn(false);
    $users->shouldReceive('save')->once();

    $handler = new UpdateUserHandler($users, $dispatcher);
    $handler->handle(new UpdateUserCommand($user->id()->toString(), 'new@example.com', null, null, null));

    expect($user->email()->value())->toBe('new@example.com');
});

it('бросает DuplicateEmailException если email занят другим пользователем', function (): void {
    $users = Mockery::mock(UserRepositoryInterface::class);
    $dispatcher = new RecordingEventDispatcher;

    $user = makeStoredUser();
    $user->pullDomainEvents();

    $users->shouldReceive('findById')->once()->andReturn($user);
    $users->shouldReceive('existsByEmail')->once()->andReturn(true);

    $handler = new UpdateUserHandler($users, $dispatcher);
    $handler->handle(new UpdateUserCommand($user->id()->toString(), 'taken@example.com', null, null, null));
})->throws(DuplicateEmailException::class);

it('не вызывает existsByEmail если новый email совпадает с текущим', function (): void {
    $users = Mockery::mock(UserRepositoryInterface::class);
    $dispatcher = new RecordingEventDispatcher;

    $user = makeStoredUser();
    $user->pullDomainEvents();

    $users->shouldReceive('findById')->once()->andReturn($user);
    $users->shouldReceive('existsByEmail')->never();
    $users->shouldReceive('save')->once();

    $handler = new UpdateUserHandler($users, $dispatcher);
    $handler->handle(new UpdateUserCommand($user->id()->toString(), 'old@example.com', null, null, null));
});

it('обновляет только firstName, сохраняя остальные части имени', function (): void {
    $users = Mockery::mock(UserRepositoryInterface::class);
    $dispatcher = new RecordingEventDispatcher;

    $user = makeStoredUser();
    $user->pullDomainEvents();

    $users->shouldReceive('findById')->once()->andReturn($user);
    $users->shouldReceive('save')->once();

    $handler = new UpdateUserHandler($users, $dispatcher);
    $handler->handle(new UpdateUserCommand($user->id()->toString(), null, 'Jane', null, null));

    expect($user->fullName()->firstName())->toBe('Jane')
        ->and($user->fullName()->lastName())->toBe('Doe')
        ->and($user->fullName()->middleName())->toBeNull();
});

it('partial update с all null — вызывает save без изменений', function (): void {
    $users = Mockery::mock(UserRepositoryInterface::class);
    $dispatcher = new RecordingEventDispatcher;

    $user = makeStoredUser();
    $user->pullDomainEvents();

    $users->shouldReceive('findById')->once()->andReturn($user);
    $users->shouldReceive('save')->once();

    $handler = new UpdateUserHandler($users, $dispatcher);
    $handler->handle(new UpdateUserCommand($user->id()->toString(), null, null, null, null));

    // данные не изменились
    expect($user->email()->value())->toBe('old@example.com')
        ->and($user->fullName()->firstName())->toBe('John');
});

it('бросает RuntimeException если пользователь не найден', function (): void {
    $users = Mockery::mock(UserRepositoryInterface::class);
    $users->shouldReceive('findById')->once()->andReturnNull();

    $handler = new UpdateUserHandler($users, new RecordingEventDispatcher);
    $handler->handle(new UpdateUserCommand(UserId::generate()->toString(), 'x@x.com', null, null, null));
})->throws(RuntimeException::class);
