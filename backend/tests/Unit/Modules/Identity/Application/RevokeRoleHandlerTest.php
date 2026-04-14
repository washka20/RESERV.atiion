<?php

declare(strict_types=1);

use App\Modules\Identity\Application\Command\RevokeRole\RevokeRoleCommand;
use App\Modules\Identity\Application\Command\RevokeRole\RevokeRoleHandler;
use App\Modules\Identity\Domain\Entity\Role;
use App\Modules\Identity\Domain\Entity\User;
use App\Modules\Identity\Domain\Event\UserRoleRevoked;
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

it('отзывает роль и диспатчит UserRoleRevoked', function (): void {
    $users = Mockery::mock(UserRepositoryInterface::class);
    $roles = Mockery::mock(RoleRepositoryInterface::class);
    $dispatcher = new RecordingEventDispatcher;
    $hasher = new InMemoryPasswordHasher;

    $userId = UserId::generate();
    $user = User::register($userId, new Email('r@example.com'), HashedPassword::fromPlaintext('pass', $hasher), new FullName('A', 'B', null));
    $user->pullDomainEvents();

    $role = new Role(RoleId::generate(), RoleName::Admin);
    $user->assignRole($role);
    $user->pullDomainEvents(); // очищаем UserRoleAssigned

    $users->shouldReceive('findById')->once()->andReturn($user);
    $users->shouldReceive('save')->once()->with(Mockery::type(User::class));
    $roles->shouldReceive('findByName')->with(RoleName::Admin)->once()->andReturn($role);

    $handler = new RevokeRoleHandler($users, $roles, $dispatcher);
    $handler->handle(new RevokeRoleCommand($userId->toString(), RoleName::Admin));

    expect($dispatcher->events)->toHaveCount(1)
        ->and($dispatcher->events[0])->toBeInstanceOf(UserRoleRevoked::class);
    expect($user->roles())->toBe([]);
});

it('бросает RuntimeException если пользователь не найден', function (): void {
    $users = Mockery::mock(UserRepositoryInterface::class);
    $roles = Mockery::mock(RoleRepositoryInterface::class);

    $users->shouldReceive('findById')->once()->andReturnNull();

    $handler = new RevokeRoleHandler($users, $roles, new RecordingEventDispatcher);
    $handler->handle(new RevokeRoleCommand(UserId::generate()->toString(), RoleName::Admin));
})->throws(RuntimeException::class);

it('бросает RuntimeException если роль не найдена', function (): void {
    $users = Mockery::mock(UserRepositoryInterface::class);
    $roles = Mockery::mock(RoleRepositoryInterface::class);
    $hasher = new InMemoryPasswordHasher;

    $userId = UserId::generate();
    $user = User::register($userId, new Email('r2@example.com'), HashedPassword::fromPlaintext('pass', $hasher), new FullName('A', 'B', null));
    $user->pullDomainEvents();

    $users->shouldReceive('findById')->once()->andReturn($user);
    $roles->shouldReceive('findByName')->once()->andReturnNull();

    $handler = new RevokeRoleHandler($users, $roles, new RecordingEventDispatcher);
    $handler->handle(new RevokeRoleCommand($userId->toString(), RoleName::Admin));
})->throws(RuntimeException::class);
