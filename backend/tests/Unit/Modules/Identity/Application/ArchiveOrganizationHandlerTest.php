<?php

declare(strict_types=1);

use App\Modules\Identity\Application\Command\ArchiveOrganization\ArchiveOrganizationCommand;
use App\Modules\Identity\Application\Command\ArchiveOrganization\ArchiveOrganizationHandler;
use App\Modules\Identity\Domain\Entity\Membership;
use App\Modules\Identity\Domain\Entity\Organization;
use App\Modules\Identity\Domain\Event\OrganizationArchived;
use App\Modules\Identity\Domain\Exception\OrganizationArchivedException;
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

function makeOrgForArchive(): Organization
{
    $org = Organization::create(
        id: OrganizationId::generate(),
        slug: new OrganizationSlug('salon-arch'),
        name: ['ru' => 'Тест'],
        description: [],
        type: OrganizationType::SALON,
        city: 'Москва',
        phone: '+7999111',
        email: 'arch@example.com',
    );
    $org->pullDomainEvents();

    return $org;
}

function makeOwnerMembership(UserId $user, OrganizationId $org): Membership
{
    $m = Membership::grant(MembershipId::generate(), $user, $org, MembershipRole::OWNER);
    $m->pullDomainEvents();

    return $m;
}

it('archives organization via happy path (owner) and dispatches event', function (): void {
    $org = makeOrgForArchive();
    $actor = UserId::generate();
    $owner = makeOwnerMembership($actor, $org->id);

    $orgs = Mockery::mock(OrganizationRepositoryInterface::class);
    $orgs->shouldReceive('findBySlug')->once()->andReturn($org);
    $orgs->shouldReceive('save')->once()->with(Mockery::type(Organization::class));

    $memberships = Mockery::mock(MembershipRepositoryInterface::class);
    $memberships->shouldReceive('findByPair')->once()->andReturn($owner);

    $dispatcher = new RecordingEventDispatcher;
    $handler = new ArchiveOrganizationHandler($orgs, $memberships, $dispatcher, new PassthroughTransactionManager);

    $handler->handle(new ArchiveOrganizationCommand('salon-arch', $actor->toString()));

    expect($org->isArchived())->toBeTrue();
    expect($dispatcher->events)->toHaveCount(1);
    expect($dispatcher->events[0])->toBeInstanceOf(OrganizationArchived::class);
});

it('forbids archive when actor is admin (not owner)', function (): void {
    $org = makeOrgForArchive();
    $actor = UserId::generate();
    $admin = Membership::grant(MembershipId::generate(), $actor, $org->id, MembershipRole::ADMIN);
    $admin->pullDomainEvents();

    $orgs = Mockery::mock(OrganizationRepositoryInterface::class);
    $orgs->shouldReceive('findBySlug')->once()->andReturn($org);

    $memberships = Mockery::mock(MembershipRepositoryInterface::class);
    $memberships->shouldReceive('findByPair')->once()->andReturn($admin);

    $handler = new ArchiveOrganizationHandler($orgs, $memberships, new RecordingEventDispatcher, new PassthroughTransactionManager);

    $handler->handle(new ArchiveOrganizationCommand('salon-arch', $actor->toString()));
})->throws(RuntimeException::class, 'Forbidden');

it('throws OrganizationNotFoundException for unknown slug', function (): void {
    $orgs = Mockery::mock(OrganizationRepositoryInterface::class);
    $orgs->shouldReceive('findBySlug')->once()->andReturnNull();

    $memberships = Mockery::mock(MembershipRepositoryInterface::class);

    $handler = new ArchiveOrganizationHandler($orgs, $memberships, new RecordingEventDispatcher, new PassthroughTransactionManager);

    $handler->handle(new ArchiveOrganizationCommand('unknown', UserId::generate()->toString()));
})->throws(OrganizationNotFoundException::class);

it('throws when archiving already-archived organization', function (): void {
    $org = makeOrgForArchive();
    $org->archive();
    $org->pullDomainEvents();

    $actor = UserId::generate();
    $owner = makeOwnerMembership($actor, $org->id);

    $orgs = Mockery::mock(OrganizationRepositoryInterface::class);
    $orgs->shouldReceive('findBySlug')->once()->andReturn($org);

    $memberships = Mockery::mock(MembershipRepositoryInterface::class);
    $memberships->shouldReceive('findByPair')->once()->andReturn($owner);

    $handler = new ArchiveOrganizationHandler($orgs, $memberships, new RecordingEventDispatcher, new PassthroughTransactionManager);

    $handler->handle(new ArchiveOrganizationCommand('salon-arch', $actor->toString()));
})->throws(OrganizationArchivedException::class);
