<?php

declare(strict_types=1);

use App\Modules\Identity\Application\Command\AssignRole\AssignRoleCommand;
use App\Modules\Identity\Application\Command\AssignRole\AssignRoleHandler;
use App\Modules\Identity\Domain\Entity\Role;
use App\Modules\Identity\Domain\Entity\User;
use App\Modules\Identity\Domain\Event\UserRoleAssigned;
use App\Modules\Identity\Domain\Repository\RoleRepositoryInterface;
use App\Modules\Identity\Domain\Repository\UserRepositoryInterface;
use App\Modules\Identity\Domain\ValueObject\Email;
use App\Modules\Identity\Domain\ValueObject\FullName;
use App\Modules\Identity\Domain\ValueObject\HashedPassword;
use App\Modules\Identity\Domain\ValueObject\RoleId;
use App\Modules\Identity\Domain\ValueObject\RoleName;
use App\Modules\Identity\Domain\ValueObject\UserId;
use Tests\Unit\Modules\Identity\Application\Support\InMemoryPasswordHasher;
use Tests\Unit\Modules\Identity\Application\Support\RecordingEventDispatcher;

it('assigns role to user and dispatches UserRoleAssigned', function (): void {
    $users = Mockery::mock(UserRepositoryInterface::class);
    $roles = Mockery::mock(RoleRepositoryInterface::class);
    $dispatcher = new RecordingEventDispatcher;
    $hasher = new InMemoryPasswordHasher;

    $userId = UserId::generate();
    $user = User::register($userId, new Email('test@example.com'), HashedPassword::fromPlaintext('secret', $hasher), new FullName('A', 'B', null));
    $user->pullDomainEvents(); // очищаем UserRegistered

    $role = new Role(RoleId::generate(), RoleName::Admin);

    $users->shouldReceive('findById')->once()->andReturn($user);
    $users->shouldReceive('save')->once()->with(Mockery::type(User::class));
    $roles->shouldReceive('findByName')->with(RoleName::Admin)->once()->andReturn($role);

    $handler = new AssignRoleHandler($users, $roles, $dispatcher);
    $handler->handle(new AssignRoleCommand((string) $userId, RoleName::Admin));

    expect($dispatcher->events)->toHaveCount(1);
    expect($dispatcher->events[0])->toBeInstanceOf(UserRoleAssigned::class);
});

it('throws RuntimeException when user not found', function (): void {
    $users = Mockery::mock(UserRepositoryInterface::class);
    $roles = Mockery::mock(RoleRepositoryInterface::class);

    $users->shouldReceive('findById')->once()->andReturnNull();

    $handler = new AssignRoleHandler($users, $roles, new RecordingEventDispatcher);
    $handler->handle(new AssignRoleCommand(UserId::generate()->toString(), RoleName::Admin));
})->throws(RuntimeException::class);

it('throws RuntimeException when role not found', function (): void {
    $users = Mockery::mock(UserRepositoryInterface::class);
    $roles = Mockery::mock(RoleRepositoryInterface::class);
    $hasher = new InMemoryPasswordHasher;

    $userId = UserId::generate();
    $user = User::register($userId, new Email('test@example.com'), HashedPassword::fromPlaintext('secret', $hasher), new FullName('A', 'B', null));
    $user->pullDomainEvents();

    $users->shouldReceive('findById')->once()->andReturn($user);
    $roles->shouldReceive('findByName')->once()->andReturnNull();

    $handler = new AssignRoleHandler($users, $roles, new RecordingEventDispatcher);
    $handler->handle(new AssignRoleCommand((string) $userId, RoleName::Admin));
})->throws(RuntimeException::class);
