<?php

declare(strict_types=1);

use App\Modules\Identity\Domain\Entity\Membership;
use App\Modules\Identity\Domain\Event\MembershipGranted;
use App\Modules\Identity\Domain\Event\MembershipRevoked;
use App\Modules\Identity\Domain\Event\MembershipRoleChanged;
use App\Modules\Identity\Domain\ValueObject\MembershipId;
use App\Modules\Identity\Domain\ValueObject\MembershipRole;
use App\Modules\Identity\Domain\ValueObject\OrganizationId;
use App\Modules\Identity\Domain\ValueObject\UserId;

function makeMembership(MembershipRole $role = MembershipRole::OWNER, ?UserId $invitedBy = null): Membership
{
    return Membership::grant(
        id: MembershipId::generate(),
        userId: UserId::generate(),
        organizationId: OrganizationId::generate(),
        role: $role,
        invitedBy: $invitedBy,
    );
}

function membershipContainsEventOfType(array $events, string $class): bool
{
    foreach ($events as $event) {
        if ($event instanceof $class) {
            return true;
        }
    }

    return false;
}

it('grants membership and records MembershipGranted event', function (): void {
    $inviter = UserId::generate();
    $membership = makeMembership(MembershipRole::ADMIN, $inviter);

    expect($membership->role())->toBe(MembershipRole::ADMIN);
    expect($membership->acceptedAt())->not->toBeNull();
    expect($membership->invitedBy)->toBe($inviter);
    expect(membershipContainsEventOfType($membership->pullDomainEvents(), MembershipGranted::class))->toBeTrue();
});

it('grants membership without inviter for self-created owner', function (): void {
    $membership = makeMembership(MembershipRole::OWNER);

    expect($membership->invitedBy)->toBeNull();
    expect($membership->role())->toBe(MembershipRole::OWNER);
});

it('changes role and records MembershipRoleChanged event with old and new', function (): void {
    $membership = makeMembership(MembershipRole::OWNER);
    $membership->pullDomainEvents();

    $membership->changeRole(MembershipRole::ADMIN);

    expect($membership->role())->toBe(MembershipRole::ADMIN);

    $events = $membership->pullDomainEvents();
    $event = null;
    foreach ($events as $e) {
        if ($e instanceof MembershipRoleChanged) {
            $event = $e;
            break;
        }
    }

    expect($event)->not->toBeNull();
    expect($event->oldRole())->toBe(MembershipRole::OWNER);
    expect($event->newRole())->toBe(MembershipRole::ADMIN);
});

it('changeRole is idempotent when setting same role', function (): void {
    $membership = makeMembership(MembershipRole::ADMIN);
    $membership->pullDomainEvents();

    $membership->changeRole(MembershipRole::ADMIN);

    expect($membership->role())->toBe(MembershipRole::ADMIN);
    expect($membership->pullDomainEvents())->toBe([]);
});

it('bumps updatedAt when role changes', function (): void {
    $membership = makeMembership(MembershipRole::OWNER);
    $before = $membership->updatedAt();
    usleep(1000);

    $membership->changeRole(MembershipRole::STAFF);

    expect($membership->updatedAt())->not->toEqual($before);
});

it('revoke records MembershipRevoked event with entity ids', function (): void {
    $membership = makeMembership(MembershipRole::ADMIN);
    $membership->pullDomainEvents();

    $membership->revoke();

    $events = $membership->pullDomainEvents();
    expect($events)->toHaveCount(1);
    expect($events[0])->toBeInstanceOf(MembershipRevoked::class);
    expect($events[0]->membershipId()->equals($membership->id))->toBeTrue();
    expect($events[0]->userId()->equals($membership->userId))->toBeTrue();
    expect($events[0]->organizationId()->equals($membership->organizationId))->toBeTrue();
});

it('reconstitute does not record domain events', function (): void {
    $membership = Membership::reconstitute(
        id: MembershipId::generate(),
        userId: UserId::generate(),
        organizationId: OrganizationId::generate(),
        role: MembershipRole::STAFF,
        invitedBy: UserId::generate(),
        acceptedAt: new DateTimeImmutable('yesterday'),
        createdAt: new DateTimeImmutable('-2 days'),
        updatedAt: new DateTimeImmutable('yesterday'),
    );

    expect($membership->role())->toBe(MembershipRole::STAFF);
    expect($membership->acceptedAt())->not->toBeNull();
    expect($membership->pullDomainEvents())->toBe([]);
});
