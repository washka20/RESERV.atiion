<?php

declare(strict_types=1);

use App\Modules\Identity\Application\DTO\MembershipWithOrgDTO;
use App\Modules\Identity\Application\Query\ListUserMemberships\ListUserMembershipsHandler;
use App\Modules\Identity\Application\Query\ListUserMemberships\ListUserMembershipsQuery;
use App\Modules\Identity\Domain\Entity\Membership;
use App\Modules\Identity\Domain\Entity\Organization;
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

function seedOrgForList(string $slug): Organization
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

it('returns empty array when user has no memberships', function (): void {
    $user = UserModel::factory()->create();

    $handler = new ListUserMembershipsHandler;
    $result = $handler->handle(new ListUserMembershipsQuery((string) $user->id));

    expect($result)->toBe([]);
});

it('returns memberships with org slug joined', function (): void {
    $user = UserModel::factory()->create();
    $userId = new UserId($user->id);

    $org1 = seedOrgForList('org-one');
    $org2 = seedOrgForList('org-two');

    $repo = new EloquentMembershipRepository;
    $repo->save(Membership::grant(MembershipId::generate(), $userId, $org1->id, MembershipRole::OWNER));
    $repo->save(Membership::grant(MembershipId::generate(), $userId, $org2->id, MembershipRole::STAFF));

    $handler = new ListUserMembershipsHandler;
    $result = $handler->handle(new ListUserMembershipsQuery((string) $user->id));

    expect($result)->toHaveCount(2);
    expect($result[0])->toBeInstanceOf(MembershipWithOrgDTO::class);

    $slugs = array_map(fn (MembershipWithOrgDTO $dto) => $dto->organizationSlug, $result);
    expect($slugs)->toContain('org-one');
    expect($slugs)->toContain('org-two');

    $roles = array_map(fn (MembershipWithOrgDTO $dto) => $dto->role, $result);
    expect($roles)->toContain('owner');
    expect($roles)->toContain('staff');
});

it('skips archived organizations', function (): void {
    $user = UserModel::factory()->create();
    $userId = new UserId($user->id);

    $active = seedOrgForList('org-active');
    $archived = seedOrgForList('org-archived');
    $archived->archive();
    (new EloquentOrganizationRepository)->save($archived);

    $memberships = new EloquentMembershipRepository;
    $memberships->save(Membership::grant(MembershipId::generate(), $userId, $active->id, MembershipRole::OWNER));
    $memberships->save(Membership::grant(MembershipId::generate(), $userId, $archived->id, MembershipRole::OWNER));

    $handler = new ListUserMembershipsHandler;
    $result = $handler->handle(new ListUserMembershipsQuery((string) $user->id));

    expect($result)->toHaveCount(1);
    expect($result[0]->organizationSlug)->toBe('org-active');
});
