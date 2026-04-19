<?php

declare(strict_types=1);

use App\Modules\Identity\Application\Command\InviteMember\InviteMemberCommand;
use App\Modules\Identity\Application\Command\InviteMember\InviteMemberHandler;
use App\Modules\Identity\Application\DTO\MembershipDTO;
use App\Modules\Identity\Domain\Entity\Membership;
use App\Modules\Identity\Domain\Entity\Organization;
use App\Modules\Identity\Domain\Entity\User;
use App\Modules\Identity\Domain\Event\MembershipGranted;
use App\Modules\Identity\Domain\Exception\MembershipAlreadyExistsException;
use App\Modules\Identity\Domain\Exception\OrganizationNotFoundException;
use App\Modules\Identity\Domain\Exception\UserNotFoundException;
use App\Modules\Identity\Domain\Repository\MembershipRepositoryInterface;
use App\Modules\Identity\Domain\Repository\OrganizationRepositoryInterface;
use App\Modules\Identity\Domain\Repository\UserRepositoryInterface;
use App\Modules\Identity\Domain\ValueObject\Email;
use App\Modules\Identity\Domain\ValueObject\FullName;
use App\Modules\Identity\Domain\ValueObject\HashedPassword;
use App\Modules\Identity\Domain\ValueObject\MembershipId;
use App\Modules\Identity\Domain\ValueObject\MembershipRole;
use App\Modules\Identity\Domain\ValueObject\OrganizationId;
use App\Modules\Identity\Domain\ValueObject\OrganizationSlug;
use App\Modules\Identity\Domain\ValueObject\OrganizationType;
use App\Modules\Identity\Domain\ValueObject\UserId;
use Tests\Unit\Modules\Identity\Application\Support\InMemoryPasswordHasher;
use Tests\Unit\Modules\Identity\Application\Support\PassthroughTransactionManager;
use Tests\Unit\Modules\Identity\Application\Support\RecordingEventDispatcher;

function makeOrgForInvite(): Organization
{
    $org = Organization::create(
        id: OrganizationId::generate(),
        slug: new OrganizationSlug('salon-inv'),
        name: ['ru' => 'Тест'],
        description: [],
        type: OrganizationType::SALON,
        city: 'Москва',
        phone: '+7999111',
        email: 'inv@example.com',
    );
    $org->pullDomainEvents();

    return $org;
}

function makeInvitedUser(string $email = 'invitee@example.com'): User
{
    return User::register(
        UserId::generate(),
        new Email($email),
        HashedPassword::fromPlaintext('password123', new InMemoryPasswordHasher),
        new FullName('Invited', 'User', null),
    );
}

it('invites existing user as staff and dispatches MembershipGranted', function (): void {
    $org = makeOrgForInvite();
    $actor = UserId::generate();
    $ownerMembership = Membership::grant(MembershipId::generate(), $actor, $org->id, MembershipRole::OWNER);
    $ownerMembership->pullDomainEvents();
    $invitee = makeInvitedUser();

    $orgs = Mockery::mock(OrganizationRepositoryInterface::class);
    $orgs->shouldReceive('findBySlug')->once()->andReturn($org);

    $memberships = Mockery::mock(MembershipRepositoryInterface::class);
    $memberships->shouldReceive('findByPair')
        ->once()
        ->with(Mockery::on(fn (UserId $uid) => $uid->equals($actor)), Mockery::any())
        ->andReturn($ownerMembership);
    $memberships->shouldReceive('findByPair')
        ->once()
        ->with(Mockery::on(fn (UserId $uid) => $uid->equals($invitee->id())), Mockery::any())
        ->andReturnNull();
    $memberships->shouldReceive('save')->once()->with(Mockery::type(Membership::class));

    $users = Mockery::mock(UserRepositoryInterface::class);
    $users->shouldReceive('findByEmail')->once()->andReturn($invitee);

    $dispatcher = new RecordingEventDispatcher;
    $handler = new InviteMemberHandler($orgs, $memberships, $users, $dispatcher, new PassthroughTransactionManager);

    $dto = $handler->handle(new InviteMemberCommand(
        organizationSlug: 'salon-inv',
        actorUserId: $actor->toString(),
        inviteeEmail: 'invitee@example.com',
        role: 'staff',
    ));

    expect($dto)->toBeInstanceOf(MembershipDTO::class);
    expect($dto->role)->toBe('staff');
    expect($dto->invitedBy)->toBe($actor->toString());
    expect($dispatcher->events)->toHaveCount(1);
    expect($dispatcher->events[0])->toBeInstanceOf(MembershipGranted::class);
});

it('rejects invite with owner role', function (): void {
    $orgs = Mockery::mock(OrganizationRepositoryInterface::class);
    $memberships = Mockery::mock(MembershipRepositoryInterface::class);
    $users = Mockery::mock(UserRepositoryInterface::class);

    $handler = new InviteMemberHandler($orgs, $memberships, $users, new RecordingEventDispatcher, new PassthroughTransactionManager);

    $handler->handle(new InviteMemberCommand(
        organizationSlug: 'salon-inv',
        actorUserId: UserId::generate()->toString(),
        inviteeEmail: 'x@example.com',
        role: 'owner',
    ));
})->throws(InvalidArgumentException::class);

it('throws OrganizationNotFoundException if slug missing', function (): void {
    $orgs = Mockery::mock(OrganizationRepositoryInterface::class);
    $orgs->shouldReceive('findBySlug')->once()->andReturnNull();

    $memberships = Mockery::mock(MembershipRepositoryInterface::class);
    $users = Mockery::mock(UserRepositoryInterface::class);

    $handler = new InviteMemberHandler($orgs, $memberships, $users, new RecordingEventDispatcher, new PassthroughTransactionManager);

    $handler->handle(new InviteMemberCommand('no-such', UserId::generate()->toString(), 'x@example.com', 'staff'));
})->throws(OrganizationNotFoundException::class);

it('forbids invite when actor is not owner (admin cannot invite)', function (): void {
    $org = makeOrgForInvite();
    $actor = UserId::generate();
    $admin = Membership::grant(MembershipId::generate(), $actor, $org->id, MembershipRole::ADMIN);
    $admin->pullDomainEvents();

    $orgs = Mockery::mock(OrganizationRepositoryInterface::class);
    $orgs->shouldReceive('findBySlug')->once()->andReturn($org);

    $memberships = Mockery::mock(MembershipRepositoryInterface::class);
    $memberships->shouldReceive('findByPair')->once()->andReturn($admin);

    $users = Mockery::mock(UserRepositoryInterface::class);

    $handler = new InviteMemberHandler($orgs, $memberships, $users, new RecordingEventDispatcher, new PassthroughTransactionManager);

    $handler->handle(new InviteMemberCommand('salon-inv', $actor->toString(), 'x@example.com', 'staff'));
})->throws(RuntimeException::class, 'Forbidden');

it('throws UserNotFoundException for unregistered invitee', function (): void {
    $org = makeOrgForInvite();
    $actor = UserId::generate();
    $owner = Membership::grant(MembershipId::generate(), $actor, $org->id, MembershipRole::OWNER);
    $owner->pullDomainEvents();

    $orgs = Mockery::mock(OrganizationRepositoryInterface::class);
    $orgs->shouldReceive('findBySlug')->once()->andReturn($org);

    $memberships = Mockery::mock(MembershipRepositoryInterface::class);
    $memberships->shouldReceive('findByPair')->once()->andReturn($owner);

    $users = Mockery::mock(UserRepositoryInterface::class);
    $users->shouldReceive('findByEmail')->once()->andReturnNull();

    $handler = new InviteMemberHandler($orgs, $memberships, $users, new RecordingEventDispatcher, new PassthroughTransactionManager);

    $handler->handle(new InviteMemberCommand('salon-inv', $actor->toString(), 'unknown@example.com', 'staff'));
})->throws(UserNotFoundException::class);

it('throws MembershipAlreadyExistsException if user already has membership', function (): void {
    $org = makeOrgForInvite();
    $actor = UserId::generate();
    $owner = Membership::grant(MembershipId::generate(), $actor, $org->id, MembershipRole::OWNER);
    $owner->pullDomainEvents();
    $invitee = makeInvitedUser();
    $existing = Membership::grant(MembershipId::generate(), $invitee->id(), $org->id, MembershipRole::STAFF);
    $existing->pullDomainEvents();

    $orgs = Mockery::mock(OrganizationRepositoryInterface::class);
    $orgs->shouldReceive('findBySlug')->once()->andReturn($org);

    $memberships = Mockery::mock(MembershipRepositoryInterface::class);
    $memberships->shouldReceive('findByPair')
        ->once()
        ->with(Mockery::on(fn (UserId $uid) => $uid->equals($actor)), Mockery::any())
        ->andReturn($owner);
    $memberships->shouldReceive('findByPair')
        ->once()
        ->with(Mockery::on(fn (UserId $uid) => $uid->equals($invitee->id())), Mockery::any())
        ->andReturn($existing);

    $users = Mockery::mock(UserRepositoryInterface::class);
    $users->shouldReceive('findByEmail')->once()->andReturn($invitee);

    $handler = new InviteMemberHandler($orgs, $memberships, $users, new RecordingEventDispatcher, new PassthroughTransactionManager);

    $handler->handle(new InviteMemberCommand('salon-inv', $actor->toString(), 'invitee@example.com', 'staff'));
})->throws(MembershipAlreadyExistsException::class);
