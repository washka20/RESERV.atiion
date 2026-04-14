<?php

declare(strict_types=1);

use App\Modules\Identity\Application\Command\RegisterUser\RegisterUserCommand;
use App\Modules\Identity\Application\Command\RegisterUser\RegisterUserHandler;
use App\Modules\Identity\Domain\Entity\Role;
use App\Modules\Identity\Domain\Entity\User;
use App\Modules\Identity\Domain\Event\UserRegistered;
use App\Modules\Identity\Domain\Event\UserRoleAssigned;
use App\Modules\Identity\Domain\Exception\DuplicateEmailException;
use App\Modules\Identity\Domain\Repository\RoleRepositoryInterface;
use App\Modules\Identity\Domain\Repository\UserRepositoryInterface;
use App\Modules\Identity\Domain\ValueObject\RoleId;
use App\Modules\Identity\Domain\ValueObject\RoleName;
use App\Modules\Identity\Domain\ValueObject\UserId;
use Tests\Unit\Modules\Identity\Application\Support\InMemoryPasswordHasher;
use Tests\Unit\Modules\Identity\Application\Support\RecordingEventDispatcher;

it('registers new user and dispatches UserRegistered', function (): void {
    $users = Mockery::mock(UserRepositoryInterface::class);
    $roles = Mockery::mock(RoleRepositoryInterface::class);
    $dispatcher = new RecordingEventDispatcher();

    $users->shouldReceive('existsByEmail')->once()->andReturnFalse();
    $users->shouldReceive('save')->once()->with(Mockery::type(User::class));
    $roles->shouldReceive('findByName')->with(RoleName::User)->once()->andReturnNull();

    $handler = new RegisterUserHandler($users, $roles, new InMemoryPasswordHasher(), $dispatcher);

    $userId = $handler->handle(new RegisterUserCommand(
        email: 'a@b.com',
        plaintextPassword: 'password123',
        firstName: 'John',
        lastName: 'Doe',
        middleName: null,
    ));

    expect($userId)->toBeInstanceOf(UserId::class);
    expect($dispatcher->events)->toHaveCount(1);
    expect($dispatcher->events[0])->toBeInstanceOf(UserRegistered::class);
});

it('throws DuplicateEmailException if email already taken', function (): void {
    $users = Mockery::mock(UserRepositoryInterface::class);
    $roles = Mockery::mock(RoleRepositoryInterface::class);

    $users->shouldReceive('existsByEmail')->once()->andReturnTrue();

    $handler = new RegisterUserHandler($users, $roles, new InMemoryPasswordHasher(), new RecordingEventDispatcher());

    $handler->handle(new RegisterUserCommand('a@b.com', 'pw12345678', 'A', 'B', null));
})->throws(DuplicateEmailException::class);

it('assigns default User role if found and dispatches two events', function (): void {
    $users = Mockery::mock(UserRepositoryInterface::class);
    $roles = Mockery::mock(RoleRepositoryInterface::class);
    $dispatcher = new RecordingEventDispatcher();

    $defaultRole = new Role(RoleId::generate(), RoleName::User);
    $users->shouldReceive('existsByEmail')->once()->andReturnFalse();
    $users->shouldReceive('save')->once();
    $roles->shouldReceive('findByName')->with(RoleName::User)->once()->andReturn($defaultRole);

    $handler = new RegisterUserHandler($users, $roles, new InMemoryPasswordHasher(), $dispatcher);
    $handler->handle(new RegisterUserCommand('a@b.com', 'pw12345678', 'A', 'B', null));

    expect($dispatcher->events)->toHaveCount(2);
    expect($dispatcher->events[0])->toBeInstanceOf(UserRegistered::class);
    expect($dispatcher->events[1])->toBeInstanceOf(UserRoleAssigned::class);
});

it('returns valid UserId from handle', function (): void {
    $users = Mockery::mock(UserRepositoryInterface::class);
    $roles = Mockery::mock(RoleRepositoryInterface::class);

    $users->shouldReceive('existsByEmail')->once()->andReturnFalse();
    $users->shouldReceive('save')->once();
    $roles->shouldReceive('findByName')->once()->andReturnNull();

    $handler = new RegisterUserHandler($users, $roles, new InMemoryPasswordHasher(), new RecordingEventDispatcher());
    $userId = $handler->handle(new RegisterUserCommand('test@example.com', 'password123', 'Test', 'User', null));

    expect((string) $userId)->toMatch('/^[0-9a-f-]{36}$/');
});
