<?php

declare(strict_types=1);

use App\Modules\Identity\Application\DTO\MemberListItemDTO;
use App\Modules\Identity\Application\Query\ListOrganizationMembers\ListOrganizationMembersHandler;
use App\Modules\Identity\Application\Query\ListOrganizationMembers\ListOrganizationMembersQuery;
use App\Modules\Identity\Domain\Entity\Membership;
use App\Modules\Identity\Domain\Entity\Organization;
use App\Modules\Identity\Domain\Exception\OrganizationNotFoundException;
use App\Modules\Identity\Domain\ValueObject\MembershipId;
use App\Modules\Identity\Domain\ValueObject\MembershipRole;
use App\Modules\Identity\Domain\ValueObject\OrganizationId;
use App\Modules\Identity\Domain\ValueObject\OrganizationSlug;
use App\Modules\Identity\Domain\ValueObject\OrganizationType;
use App\Modules\Identity\Domain\ValueObject\UserId;
use App\Modules\Identity\Infrastructure\Persistence\Model\UserModel;
use App\Modules\Identity\Infrastructure\Persistence\Repository\EloquentMembershipRepository;
use App\Modules\Identity\Infrastructure\Persistence\Repository\EloquentOrganizationRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function seedOrgForMembersList(string $slug = 'org-members'): Organization
{
    $org = Organization::create(
        id: OrganizationId::generate(),
        slug: new OrganizationSlug($slug),
        name: ['ru' => 'Test'],
        description: [],
        type: OrganizationType::SALON,
        city: 'Москва',
        phone: '+7999111',
        email: "{$slug}@example.com",
    );
    (new EloquentOrganizationRepository)->save($org);

    return $org;
}

it('returns org members with embedded user info (owner sees all)', function (): void {
    $org = seedOrgForMembersList();
    $orgRepo = new EloquentOrganizationRepository;
    $memberships = new EloquentMembershipRepository;

    $owner = UserModel::factory()->create(['first_name' => 'Olivia', 'last_name' => 'Owner']);
    $staff = UserModel::factory()->create(['first_name' => 'Sam', 'last_name' => 'Staff']);
    $ownerId = new UserId($owner->id);
    $staffId = new UserId($staff->id);

    $memberships->save(Membership::grant(MembershipId::generate(), $ownerId, $org->id, MembershipRole::OWNER));
    $memberships->save(Membership::grant(MembershipId::generate(), $staffId, $org->id, MembershipRole::STAFF, invitedBy: $ownerId));

    $handler = new ListOrganizationMembersHandler($orgRepo, $memberships);
    $result = $handler->handle(new ListOrganizationMembersQuery($org->slug->toString(), $ownerId->toString()));

    expect($result)->toHaveCount(2);
    expect($result[0])->toBeInstanceOf(MemberListItemDTO::class);

    $emails = array_map(fn (MemberListItemDTO $m) => $m->userEmail, $result);
    expect($emails)->toContain($owner->email);
    expect($emails)->toContain($staff->email);

    $roles = array_map(fn (MemberListItemDTO $m) => $m->role, $result);
    expect($roles)->toContain('owner');
    expect($roles)->toContain('staff');
});

it('throws OrganizationNotFoundException for unknown slug', function (): void {
    $orgRepo = new EloquentOrganizationRepository;
    $memberships = new EloquentMembershipRepository;

    $handler = new ListOrganizationMembersHandler($orgRepo, $memberships);
    $handler->handle(new ListOrganizationMembersQuery('no-such-org', UserId::generate()->toString()));
})->throws(OrganizationNotFoundException::class);

it('forbids list when actor is not a member', function (): void {
    $org = seedOrgForMembersList('org-forbidden');
    $orgRepo = new EloquentOrganizationRepository;
    $memberships = new EloquentMembershipRepository;

    $stranger = UserModel::factory()->create();

    $handler = new ListOrganizationMembersHandler($orgRepo, $memberships);
    $handler->handle(new ListOrganizationMembersQuery($org->slug->toString(), (string) $stranger->id));
})->throws(RuntimeException::class, 'Forbidden');

it('forbids list when actor is only viewer (lacks team.view)', function (): void {
    $org = seedOrgForMembersList('org-viewer');
    $orgRepo = new EloquentOrganizationRepository;
    $memberships = new EloquentMembershipRepository;

    $viewer = UserModel::factory()->create();
    $viewerId = new UserId($viewer->id);
    $memberships->save(Membership::grant(MembershipId::generate(), $viewerId, $org->id, MembershipRole::VIEWER));

    $handler = new ListOrganizationMembersHandler($orgRepo, $memberships);
    $handler->handle(new ListOrganizationMembersQuery($org->slug->toString(), (string) $viewer->id));
})->throws(RuntimeException::class, 'Forbidden');
