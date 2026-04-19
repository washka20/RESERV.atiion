<?php

declare(strict_types=1);

use App\Modules\Identity\Application\Command\ChangeMembershipRole\ChangeMembershipRoleCommand;
use App\Modules\Identity\Application\Command\ChangeMembershipRole\ChangeMembershipRoleHandler;
use App\Modules\Identity\Application\DTO\MembershipDTO;
use App\Modules\Identity\Domain\Entity\Membership;
use App\Modules\Identity\Domain\Entity\Organization;
use App\Modules\Identity\Domain\Event\MembershipRoleChanged;
use App\Modules\Identity\Domain\Exception\LastOwnerCannotBeRevokedException;
use App\Modules\Identity\Domain\Exception\MembershipNotFoundException;
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

function makeOrgForChangeRole(): Organization
{
    $org = Organization::create(
        id: OrganizationId::generate(),
        slug: new OrganizationSlug('salon-chg'),
        name: ['ru' => 'Тест'],
        description: [],
        type: OrganizationType::SALON,
        city: 'Москва',
        phone: '+7999111',
        email: 'chg@example.com',
    );
    $org->pullDomainEvents();

    return $org;
}

function makeChangeMembership(UserId $user, OrganizationId $org, MembershipRole $role): Membership
{
    $m = Membership::grant(MembershipId::generate(), $user, $org, $role);
    $m->pullDomainEvents();

    return $m;
}

it('changes role via happy path (owner promotes staff to admin)', function (): void {
    $org = makeOrgForChangeRole();
    $actor = UserId::generate();
    $owner = makeChangeMembership($actor, $org->id, MembershipRole::OWNER);
    $target = makeChangeMembership(UserId::generate(), $org->id, MembershipRole::STAFF);

    $orgs = Mockery::mock(OrganizationRepositoryInterface::class);
    $orgs->shouldReceive('findBySlug')->once()->andReturn($org);

    $memberships = Mockery::mock(MembershipRepositoryInterface::class);
    $memberships->shouldReceive('findByPair')->once()->andReturn($owner);
    $memberships->shouldReceive('findById')->once()->andReturn($target);
    $memberships->shouldReceive('save')->once()->with(Mockery::type(Membership::class));

    $dispatcher = new RecordingEventDispatcher;
    $handler = new ChangeMembershipRoleHandler($orgs, $memberships, $dispatcher, new PassthroughTransactionManager);

    $dto = $handler->handle(new ChangeMembershipRoleCommand(
        'salon-chg',
        $actor->toString(),
        $target->id->toString(),
        'admin',
    ));

    expect($dto)->toBeInstanceOf(MembershipDTO::class);
    expect($dto->role)->toBe('admin');
    expect($dispatcher->events)->toHaveCount(1);
    expect($dispatcher->events[0])->toBeInstanceOf(MembershipRoleChanged::class);
});

it('is idempotent when role unchanged (no events, save still called)', function (): void {
    $org = makeOrgForChangeRole();
    $actor = UserId::generate();
    $owner = makeChangeMembership($actor, $org->id, MembershipRole::OWNER);
    $target = makeChangeMembership(UserId::generate(), $org->id, MembershipRole::ADMIN);

    $orgs = Mockery::mock(OrganizationRepositoryInterface::class);
    $orgs->shouldReceive('findBySlug')->once()->andReturn($org);

    $memberships = Mockery::mock(MembershipRepositoryInterface::class);
    $memberships->shouldReceive('findByPair')->once()->andReturn($owner);
    $memberships->shouldReceive('findById')->once()->andReturn($target);
    $memberships->shouldReceive('save')->once();

    $dispatcher = new RecordingEventDispatcher;
    $handler = new ChangeMembershipRoleHandler($orgs, $memberships, $dispatcher, new PassthroughTransactionManager);

    $dto = $handler->handle(new ChangeMembershipRoleCommand(
        'salon-chg',
        $actor->toString(),
        $target->id->toString(),
        'admin',
    ));

    expect($dto->role)->toBe('admin');
    expect($dispatcher->events)->toBe([]);
});

it('forbids role change when actor is admin', function (): void {
    $org = makeOrgForChangeRole();
    $actor = UserId::generate();
    $admin = makeChangeMembership($actor, $org->id, MembershipRole::ADMIN);

    $orgs = Mockery::mock(OrganizationRepositoryInterface::class);
    $orgs->shouldReceive('findBySlug')->once()->andReturn($org);

    $memberships = Mockery::mock(MembershipRepositoryInterface::class);
    $memberships->shouldReceive('findByPair')->once()->andReturn($admin);

    $handler = new ChangeMembershipRoleHandler($orgs, $memberships, new RecordingEventDispatcher, new PassthroughTransactionManager);

    $handler->handle(new ChangeMembershipRoleCommand(
        'salon-chg',
        $actor->toString(),
        MembershipId::generate()->toString(),
        'admin',
    ));
})->throws(RuntimeException::class, 'Forbidden');

it('throws LastOwnerCannotBeRevokedException when demoting only owner', function (): void {
    $org = makeOrgForChangeRole();
    $actor = UserId::generate();
    $onlyOwner = makeChangeMembership($actor, $org->id, MembershipRole::OWNER);

    $orgs = Mockery::mock(OrganizationRepositoryInterface::class);
    $orgs->shouldReceive('findBySlug')->once()->andReturn($org);

    $memberships = Mockery::mock(MembershipRepositoryInterface::class);
    $memberships->shouldReceive('findByPair')->once()->andReturn($onlyOwner);
    $memberships->shouldReceive('findById')->once()->andReturn($onlyOwner);
    $memberships->shouldReceive('countOwnersInOrganization')->once()->andReturn(1);

    $handler = new ChangeMembershipRoleHandler($orgs, $memberships, new RecordingEventDispatcher, new PassthroughTransactionManager);

    $handler->handle(new ChangeMembershipRoleCommand(
        'salon-chg',
        $actor->toString(),
        $onlyOwner->id->toString(),
        'admin',
    ));
})->throws(LastOwnerCannotBeRevokedException::class);

it('allows demoting one of multiple owners', function (): void {
    $org = makeOrgForChangeRole();
    $actor = UserId::generate();
    $actorOwner = makeChangeMembership($actor, $org->id, MembershipRole::OWNER);
    $otherOwner = makeChangeMembership(UserId::generate(), $org->id, MembershipRole::OWNER);

    $orgs = Mockery::mock(OrganizationRepositoryInterface::class);
    $orgs->shouldReceive('findBySlug')->once()->andReturn($org);

    $memberships = Mockery::mock(MembershipRepositoryInterface::class);
    $memberships->shouldReceive('findByPair')->once()->andReturn($actorOwner);
    $memberships->shouldReceive('findById')->once()->andReturn($otherOwner);
    $memberships->shouldReceive('countOwnersInOrganization')->once()->andReturn(2);
    $memberships->shouldReceive('save')->once();

    $handler = new ChangeMembershipRoleHandler($orgs, $memberships, new RecordingEventDispatcher, new PassthroughTransactionManager);

    $dto = $handler->handle(new ChangeMembershipRoleCommand(
        'salon-chg',
        $actor->toString(),
        $otherOwner->id->toString(),
        'admin',
    ));

    expect($dto->role)->toBe('admin');
});

it('rejects target from different organization', function (): void {
    $org = makeOrgForChangeRole();
    $otherOrg = OrganizationId::generate();
    $actor = UserId::generate();
    $owner = makeChangeMembership($actor, $org->id, MembershipRole::OWNER);
    $foreignTarget = makeChangeMembership(UserId::generate(), $otherOrg, MembershipRole::STAFF);

    $orgs = Mockery::mock(OrganizationRepositoryInterface::class);
    $orgs->shouldReceive('findBySlug')->once()->andReturn($org);

    $memberships = Mockery::mock(MembershipRepositoryInterface::class);
    $memberships->shouldReceive('findByPair')->once()->andReturn($owner);
    $memberships->shouldReceive('findById')->once()->andReturn($foreignTarget);

    $handler = new ChangeMembershipRoleHandler($orgs, $memberships, new RecordingEventDispatcher, new PassthroughTransactionManager);

    $handler->handle(new ChangeMembershipRoleCommand(
        'salon-chg',
        $actor->toString(),
        $foreignTarget->id->toString(),
        'admin',
    ));
})->throws(MembershipNotFoundException::class);
