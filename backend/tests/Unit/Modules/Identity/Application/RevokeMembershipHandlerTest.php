<?php

declare(strict_types=1);

use App\Modules\Identity\Application\Command\RevokeMembership\RevokeMembershipCommand;
use App\Modules\Identity\Application\Command\RevokeMembership\RevokeMembershipHandler;
use App\Modules\Identity\Domain\Entity\Membership;
use App\Modules\Identity\Domain\Entity\Organization;
use App\Modules\Identity\Domain\Event\MembershipRevoked;
use App\Modules\Identity\Domain\Exception\LastOwnerCannotBeRevokedException;
use App\Modules\Identity\Domain\Exception\MembershipNotFoundException;
use App\Modules\Identity\Domain\Exception\OrganizationNotFoundException;
use App\Modules\Identity\Domain\Repository\MembershipRepositoryInterface;
use App\Modules\Identity\Domain\Repository\OrganizationRepositoryInterface;
use App\Modules\Identity\Domain\ValueObject\MembershipId;
use App\Modules\Identity\Domain\ValueObject\MembershipRole;
use App\Modules\Identity\Domain\ValueObject\OrganizationId;
use App\Modules\Identity\Domain\ValueObject\OrganizationSlug;
use App\Modules\Identity\Domain\ValueObject\OrganizationType;
use App\Modules\Identity\Domain\ValueObject\UserId;
use Tests\Unit\Modules\Identity\Application\Support\PassthroughTransactionManager;
use Tests\Unit\Modules\Identity\Application\Support\RecordingEventDispatcher;

function makeOrgForRevoke(): Organization
{
    $org = Organization::create(
        id: OrganizationId::generate(),
        slug: new OrganizationSlug('salon-rev'),
        name: ['ru' => 'Тест'],
        description: [],
        type: OrganizationType::SALON,
        city: 'Москва',
        phone: '+7999111',
        email: 'rev@example.com',
    );
    $org->pullDomainEvents();

    return $org;
}

function makeRevokeMembership(UserId $user, OrganizationId $org, MembershipRole $role): Membership
{
    $m = Membership::grant(MembershipId::generate(), $user, $org, $role);
    $m->pullDomainEvents();

    return $m;
}

it('revokes staff membership via happy path', function (): void {
    $org = makeOrgForRevoke();
    $actor = UserId::generate();
    $ownerM = makeRevokeMembership($actor, $org->id, MembershipRole::OWNER);
    $targetM = makeRevokeMembership(UserId::generate(), $org->id, MembershipRole::STAFF);

    $orgs = Mockery::mock(OrganizationRepositoryInterface::class);
    $orgs->shouldReceive('findBySlug')->once()->andReturn($org);

    $memberships = Mockery::mock(MembershipRepositoryInterface::class);
    $memberships->shouldReceive('findByPair')->once()->andReturn($ownerM);
    $memberships->shouldReceive('findById')->once()->andReturn($targetM);
    $memberships->shouldReceive('delete')->once()->with(Mockery::on(fn (MembershipId $id) => $id->equals($targetM->id)));

    $dispatcher = new RecordingEventDispatcher;
    $handler = new RevokeMembershipHandler($orgs, $memberships, $dispatcher, new PassthroughTransactionManager);

    $handler->handle(new RevokeMembershipCommand('salon-rev', $actor->toString(), $targetM->id->toString()));

    expect($dispatcher->events)->toHaveCount(1);
    expect($dispatcher->events[0])->toBeInstanceOf(MembershipRevoked::class);
});

it('forbids revoke when actor is admin (not owner)', function (): void {
    $org = makeOrgForRevoke();
    $actor = UserId::generate();
    $admin = makeRevokeMembership($actor, $org->id, MembershipRole::ADMIN);

    $orgs = Mockery::mock(OrganizationRepositoryInterface::class);
    $orgs->shouldReceive('findBySlug')->once()->andReturn($org);

    $memberships = Mockery::mock(MembershipRepositoryInterface::class);
    $memberships->shouldReceive('findByPair')->once()->andReturn($admin);

    $handler = new RevokeMembershipHandler($orgs, $memberships, new RecordingEventDispatcher, new PassthroughTransactionManager);

    $handler->handle(new RevokeMembershipCommand('salon-rev', $actor->toString(), MembershipId::generate()->toString()));
})->throws(RuntimeException::class, 'Forbidden');

it('throws OrganizationNotFoundException for unknown slug', function (): void {
    $orgs = Mockery::mock(OrganizationRepositoryInterface::class);
    $orgs->shouldReceive('findBySlug')->once()->andReturnNull();

    $memberships = Mockery::mock(MembershipRepositoryInterface::class);

    $handler = new RevokeMembershipHandler($orgs, $memberships, new RecordingEventDispatcher, new PassthroughTransactionManager);

    $handler->handle(new RevokeMembershipCommand('no-org', UserId::generate()->toString(), MembershipId::generate()->toString()));
})->throws(OrganizationNotFoundException::class);

it('throws MembershipNotFoundException when target missing', function (): void {
    $org = makeOrgForRevoke();
    $actor = UserId::generate();
    $owner = makeRevokeMembership($actor, $org->id, MembershipRole::OWNER);

    $orgs = Mockery::mock(OrganizationRepositoryInterface::class);
    $orgs->shouldReceive('findBySlug')->once()->andReturn($org);

    $memberships = Mockery::mock(MembershipRepositoryInterface::class);
    $memberships->shouldReceive('findByPair')->once()->andReturn($owner);
    $memberships->shouldReceive('findById')->once()->andReturnNull();

    $handler = new RevokeMembershipHandler($orgs, $memberships, new RecordingEventDispatcher, new PassthroughTransactionManager);

    $handler->handle(new RevokeMembershipCommand('salon-rev', $actor->toString(), MembershipId::generate()->toString()));
})->throws(MembershipNotFoundException::class);

it('throws LastOwnerCannotBeRevokedException when revoking only owner', function (): void {
    $org = makeOrgForRevoke();
    $actor = UserId::generate();
    $owner = makeRevokeMembership($actor, $org->id, MembershipRole::OWNER);

    $orgs = Mockery::mock(OrganizationRepositoryInterface::class);
    $orgs->shouldReceive('findBySlug')->once()->andReturn($org);

    $memberships = Mockery::mock(MembershipRepositoryInterface::class);
    $memberships->shouldReceive('findByPair')->once()->andReturn($owner);
    $memberships->shouldReceive('findById')->once()->andReturn($owner);
    $memberships->shouldReceive('countOwnersInOrganization')->once()->andReturn(1);

    $handler = new RevokeMembershipHandler($orgs, $memberships, new RecordingEventDispatcher, new PassthroughTransactionManager);

    $handler->handle(new RevokeMembershipCommand('salon-rev', $actor->toString(), $owner->id->toString()));
})->throws(LastOwnerCannotBeRevokedException::class);

it('allows revoking one owner if another owner exists', function (): void {
    $org = makeOrgForRevoke();
    $actor = UserId::generate();
    $actorOwner = makeRevokeMembership($actor, $org->id, MembershipRole::OWNER);
    $secondOwner = makeRevokeMembership(UserId::generate(), $org->id, MembershipRole::OWNER);

    $orgs = Mockery::mock(OrganizationRepositoryInterface::class);
    $orgs->shouldReceive('findBySlug')->once()->andReturn($org);

    $memberships = Mockery::mock(MembershipRepositoryInterface::class);
    $memberships->shouldReceive('findByPair')->once()->andReturn($actorOwner);
    $memberships->shouldReceive('findById')->once()->andReturn($secondOwner);
    $memberships->shouldReceive('countOwnersInOrganization')->once()->andReturn(2);
    $memberships->shouldReceive('delete')->once();

    $dispatcher = new RecordingEventDispatcher;
    $handler = new RevokeMembershipHandler($orgs, $memberships, $dispatcher, new PassthroughTransactionManager);

    $handler->handle(new RevokeMembershipCommand('salon-rev', $actor->toString(), $secondOwner->id->toString()));

    expect($dispatcher->events)->toHaveCount(1);
});

it('rejects target membership from different organization', function (): void {
    $org = makeOrgForRevoke();
    $otherOrgId = OrganizationId::generate();
    $actor = UserId::generate();
    $owner = makeRevokeMembership($actor, $org->id, MembershipRole::OWNER);
    $foreignTarget = makeRevokeMembership(UserId::generate(), $otherOrgId, MembershipRole::STAFF);

    $orgs = Mockery::mock(OrganizationRepositoryInterface::class);
    $orgs->shouldReceive('findBySlug')->once()->andReturn($org);

    $memberships = Mockery::mock(MembershipRepositoryInterface::class);
    $memberships->shouldReceive('findByPair')->once()->andReturn($owner);
    $memberships->shouldReceive('findById')->once()->andReturn($foreignTarget);

    $handler = new RevokeMembershipHandler($orgs, $memberships, new RecordingEventDispatcher, new PassthroughTransactionManager);

    $handler->handle(new RevokeMembershipCommand('salon-rev', $actor->toString(), $foreignTarget->id->toString()));
})->throws(MembershipNotFoundException::class);
