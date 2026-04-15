<?php

declare(strict_types=1);

use App\Modules\Identity\Application\Command\DeleteUser\DeleteUserCommand;
use App\Modules\Identity\Application\Command\DeleteUser\DeleteUserHandler;
use App\Modules\Identity\Domain\Entity\User;
use App\Modules\Identity\Domain\Repository\UserRepositoryInterface;
use App\Modules\Identity\Domain\ValueObject\Email;
use App\Modules\Identity\Domain\ValueObject\FullName;
use App\Modules\Identity\Domain\ValueObject\HashedPassword;
use App\Modules\Identity\Domain\ValueObject\UserId;
use Tests\Unit\Modules\Identity\Application\Support\InMemoryPasswordHasher;

it('удаляет пользователя через репозиторий', function (): void {
    $users = Mockery::mock(UserRepositoryInterface::class);
    $hasher = new InMemoryPasswordHasher;

    $userId = UserId::generate();
    $user = User::register($userId, new Email('del@example.com'), HashedPassword::fromPlaintext('pass', $hasher), new FullName('A', 'B', null));
    $user->pullDomainEvents();

    $users->shouldReceive('findById')->once()->andReturn($user);
    $users->shouldReceive('delete')->once()->with(Mockery::type(User::class));

    $handler = new DeleteUserHandler($users);
    $handler->handle(new DeleteUserCommand($userId->toString()));
});

it('бросает RuntimeException если пользователь не найден', function (): void {
    $users = Mockery::mock(UserRepositoryInterface::class);
    $users->shouldReceive('findById')->once()->andReturnNull();

    $handler = new DeleteUserHandler($users);
    $handler->handle(new DeleteUserCommand(UserId::generate()->toString()));
})->throws(RuntimeException::class);
