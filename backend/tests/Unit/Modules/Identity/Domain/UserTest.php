<?php

declare(strict_types=1);

use App\Modules\Identity\Domain\Entity\Role;
use App\Modules\Identity\Domain\Entity\User;
use App\Modules\Identity\Domain\Event\UserEmailVerified;
use App\Modules\Identity\Domain\Event\UserRegistered;
use App\Modules\Identity\Domain\Event\UserRoleAssigned;
use App\Modules\Identity\Domain\ValueObject\Email;
use App\Modules\Identity\Domain\ValueObject\FullName;
use App\Modules\Identity\Domain\ValueObject\HashedPassword;
use App\Modules\Identity\Domain\ValueObject\RoleId;
use App\Modules\Identity\Domain\ValueObject\RoleName;
use App\Modules\Identity\Domain\ValueObject\UserId;

function makeUser(): User
{
    return User::register(
        UserId::generate(),
        new Email('a@b.com'),
        new HashedPassword('hashed'),
        new FullName('John', 'Doe', null),
    );
}

it('registers user and records UserRegistered event', function (): void {
    $id = UserId::generate();
    $user = User::register(
        $id,
        new Email('a@b.com'),
        new HashedPassword('hashed'),
        new FullName('John', 'Doe', null),
    );

    expect($user->id()->equals($id))->toBeTrue()
        ->and($user->email()->value())->toBe('a@b.com')
        ->and($user->roles())->toBe([])
        ->and($user->isEmailVerified())->toBeFalse();

    $events = $user->pullDomainEvents();
    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(UserRegistered::class);
});

it('assigns role and records UserRoleAssigned event', function (): void {
    $user = makeUser();
    $user->pullDomainEvents();

    $role = new Role(RoleId::generate(), RoleName::User);
    $user->assignRole($role);

    expect($user->roles())->toHaveCount(1);
    $events = $user->pullDomainEvents();
    expect($events[0])->toBeInstanceOf(UserRoleAssigned::class);
});

it('does not duplicate roles when assigning same role twice', function (): void {
    $user = makeUser();
    $user->pullDomainEvents();

    $role = new Role(RoleId::generate(), RoleName::User);
    $user->assignRole($role);
    $user->assignRole($role);

    expect($user->roles())->toHaveCount(1);
});

it('verifies email and records UserEmailVerified event', function (): void {
    $user = makeUser();
    $user->pullDomainEvents();

    expect($user->isEmailVerified())->toBeFalse();
    $user->verifyEmail();
    expect($user->isEmailVerified())->toBeTrue();
    expect($user->pullDomainEvents()[0])->toBeInstanceOf(UserEmailVerified::class);
});

it('verifyEmail is idempotent — no duplicate events', function (): void {
    $user = makeUser();
    $user->pullDomainEvents();

    $user->verifyEmail();
    $user->verifyEmail();

    $events = $user->pullDomainEvents();
    expect($events)->toHaveCount(1);
});

it('changes password hash', function (): void {
    $user = makeUser();
    $user->pullDomainEvents();

    $user->changePassword(new HashedPassword('new-hash'));
    expect($user->passwordHash()->value())->toBe('new-hash');
});
