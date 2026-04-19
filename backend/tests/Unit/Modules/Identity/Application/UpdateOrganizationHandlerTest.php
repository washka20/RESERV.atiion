<?php

declare(strict_types=1);

use App\Modules\Identity\Application\Command\UpdateOrganization\UpdateOrganizationCommand;
use App\Modules\Identity\Application\Command\UpdateOrganization\UpdateOrganizationHandler;
use App\Modules\Identity\Application\DTO\OrganizationDTO;
use App\Modules\Identity\Domain\Entity\Membership;
use App\Modules\Identity\Domain\Entity\Organization;
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

function makeOrgForUpdate(string $slug = 'salon-test'): Organization
{
    $org = Organization::create(
        id: OrganizationId::generate(),
        slug: new OrganizationSlug($slug),
        name: ['ru' => 'Тест'],
        description: [],
        type: OrganizationType::SALON,
        city: 'Москва',
        phone: '+7999111',
        email: 'test@example.com',
    );
    $org->pullDomainEvents();

    return $org;
}

function makeMembershipFor(UserId $user, OrganizationId $org, MembershipRole $role): Membership
{
    $m = Membership::grant(MembershipId::generate(), $user, $org, $role);
    $m->pullDomainEvents();

    return $m;
}

it('updates organization details via happy path (owner)', function (): void {
    $org = makeOrgForUpdate();
    $actor = UserId::generate();
    $actorMembership = makeMembershipFor($actor, $org->id, MembershipRole::OWNER);

    $orgs = Mockery::mock(OrganizationRepositoryInterface::class);
    $orgs->shouldReceive('findBySlug')->once()->andReturn($org);
    $orgs->shouldReceive('save')->once()->with(Mockery::type(Organization::class));

    $memberships = Mockery::mock(MembershipRepositoryInterface::class);
    $memberships->shouldReceive('findByPair')->once()->andReturn($actorMembership);

    $dispatcher = new RecordingEventDispatcher;
    $handler = new UpdateOrganizationHandler($orgs, $memberships, $dispatcher, new PassthroughTransactionManager);

    $dto = $handler->handle(new UpdateOrganizationCommand(
        organizationSlug: 'salon-test',
        actorUserId: $actor->toString(),
        name: ['ru' => 'Новое имя'],
        description: ['ru' => 'Описание'],
        city: 'Казань',
        district: 'Центр',
        phone: '+7111222',
        email: 'new@example.com',
    ));

    expect($dto)->toBeInstanceOf(OrganizationDTO::class);
    expect($dto->city)->toBe('Казань');
    expect($dto->district)->toBe('Центр');
    expect($dto->email)->toBe('new@example.com');
});

it('updates organization when actor is admin (settings.manage)', function (): void {
    $org = makeOrgForUpdate();
    $actor = UserId::generate();
    $actorMembership = makeMembershipFor($actor, $org->id, MembershipRole::ADMIN);

    $orgs = Mockery::mock(OrganizationRepositoryInterface::class);
    $orgs->shouldReceive('findBySlug')->once()->andReturn($org);
    $orgs->shouldReceive('save')->once();

    $memberships = Mockery::mock(MembershipRepositoryInterface::class);
    $memberships->shouldReceive('findByPair')->once()->andReturn($actorMembership);

    $handler = new UpdateOrganizationHandler($orgs, $memberships, new RecordingEventDispatcher, new PassthroughTransactionManager);

    $dto = $handler->handle(new UpdateOrganizationCommand(
        organizationSlug: 'salon-test',
        actorUserId: $actor->toString(),
        name: ['ru' => 'Апдейт'],
        description: [],
        city: 'Сочи',
        district: null,
        phone: '+7999222',
        email: 'admin@example.com',
    ));

    expect($dto->city)->toBe('Сочи');
});

it('throws OrganizationNotFoundException when slug not found', function (): void {
    $orgs = Mockery::mock(OrganizationRepositoryInterface::class);
    $orgs->shouldReceive('findBySlug')->once()->andReturnNull();

    $memberships = Mockery::mock(MembershipRepositoryInterface::class);

    $handler = new UpdateOrganizationHandler($orgs, $memberships, new RecordingEventDispatcher, new PassthroughTransactionManager);

    $handler->handle(new UpdateOrganizationCommand(
        organizationSlug: 'missing-org',
        actorUserId: UserId::generate()->toString(),
        name: ['ru' => 'X'],
        description: [],
        city: 'Москва',
        district: null,
        phone: '+7999111',
        email: 'x@example.com',
    ));
})->throws(OrganizationNotFoundException::class);

it('forbids update when actor is not a member', function (): void {
    $org = makeOrgForUpdate();

    $orgs = Mockery::mock(OrganizationRepositoryInterface::class);
    $orgs->shouldReceive('findBySlug')->once()->andReturn($org);

    $memberships = Mockery::mock(MembershipRepositoryInterface::class);
    $memberships->shouldReceive('findByPair')->once()->andReturnNull();

    $handler = new UpdateOrganizationHandler($orgs, $memberships, new RecordingEventDispatcher, new PassthroughTransactionManager);

    $handler->handle(new UpdateOrganizationCommand(
        organizationSlug: 'salon-test',
        actorUserId: UserId::generate()->toString(),
        name: ['ru' => 'X'],
        description: [],
        city: 'Москва',
        district: null,
        phone: '+7999111',
        email: 'x@example.com',
    ));
})->throws(RuntimeException::class, 'Forbidden');

it('forbids update when actor is only staff (lacks settings.manage)', function (): void {
    $org = makeOrgForUpdate();
    $actor = UserId::generate();
    $staff = makeMembershipFor($actor, $org->id, MembershipRole::STAFF);

    $orgs = Mockery::mock(OrganizationRepositoryInterface::class);
    $orgs->shouldReceive('findBySlug')->once()->andReturn($org);

    $memberships = Mockery::mock(MembershipRepositoryInterface::class);
    $memberships->shouldReceive('findByPair')->once()->andReturn($staff);

    $handler = new UpdateOrganizationHandler($orgs, $memberships, new RecordingEventDispatcher, new PassthroughTransactionManager);

    $handler->handle(new UpdateOrganizationCommand(
        organizationSlug: 'salon-test',
        actorUserId: $actor->toString(),
        name: ['ru' => 'X'],
        description: [],
        city: 'Москва',
        district: null,
        phone: '+7999111',
        email: 'x@example.com',
    ));
})->throws(RuntimeException::class);

it('throws OrganizationArchivedException when updating archived org', function (): void {
    $org = makeOrgForUpdate();
    $org->archive();
    $org->pullDomainEvents();

    $actor = UserId::generate();
    $owner = makeMembershipFor($actor, $org->id, MembershipRole::OWNER);

    $orgs = Mockery::mock(OrganizationRepositoryInterface::class);
    $orgs->shouldReceive('findBySlug')->once()->andReturn($org);

    $memberships = Mockery::mock(MembershipRepositoryInterface::class);
    $memberships->shouldReceive('findByPair')->once()->andReturn($owner);

    $handler = new UpdateOrganizationHandler($orgs, $memberships, new RecordingEventDispatcher, new PassthroughTransactionManager);

    $handler->handle(new UpdateOrganizationCommand(
        organizationSlug: 'salon-test',
        actorUserId: $actor->toString(),
        name: ['ru' => 'X'],
        description: [],
        city: 'Москва',
        district: null,
        phone: '+7999111',
        email: 'x@example.com',
    ));
})->throws(OrganizationArchivedException::class);
