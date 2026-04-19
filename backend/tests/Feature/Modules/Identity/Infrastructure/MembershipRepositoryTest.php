<?php

declare(strict_types=1);

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

function membershipSeedOrg(?string $slug = null): Organization
{
    $repo = new EloquentOrganizationRepository;
    $org = Organization::create(
        id: OrganizationId::generate(),
        slug: new OrganizationSlug($slug ?? 'org-'.bin2hex(random_bytes(4))),
        name: ['ru' => 'Тест'],
        description: [],
        type: OrganizationType::SALON,
        city: 'Москва',
        phone: '+7-495-1234567',
        email: 'info@test.com',
    );
    $repo->save($org);

    return $org;
}

function membershipSeedUser(): UserId
{
    $user = UserModel::factory()->create();

    return new UserId($user->id);
}

it('saves and retrieves membership by id', function (): void {
    $repo = new EloquentMembershipRepository;
    $org = membershipSeedOrg();
    $userId = membershipSeedUser();

    $membership = Membership::grant(
        MembershipId::generate(),
        $userId,
        $org->id,
        MembershipRole::OWNER,
    );
    $repo->save($membership);

    $found = $repo->findById($membership->id);

    expect($found)->not->toBeNull();
    expect($found->id->toString())->toBe($membership->id->toString());
    expect($found->role())->toBe(MembershipRole::OWNER);
    expect($found->acceptedAt())->not->toBeNull();
});

it('returns null for unknown membership id', function (): void {
    $repo = new EloquentMembershipRepository;

    expect($repo->findById(MembershipId::generate()))->toBeNull();
});

it('finds membership by (user, organization) pair', function (): void {
    $repo = new EloquentMembershipRepository;
    $org = membershipSeedOrg();
    $userId = membershipSeedUser();

    $membership = Membership::grant(
        MembershipId::generate(),
        $userId,
        $org->id,
        MembershipRole::ADMIN,
    );
    $repo->save($membership);

    $found = $repo->findByPair($userId, $org->id);

    expect($found)->not->toBeNull();
    expect($found->id->toString())->toBe($membership->id->toString());
});

it('returns null when pair does not exist', function (): void {
    $repo = new EloquentMembershipRepository;

    $found = $repo->findByPair(UserId::generate(), OrganizationId::generate());

    expect($found)->toBeNull();
});

it('lists memberships by user id', function (): void {
    $repo = new EloquentMembershipRepository;
    $userId = membershipSeedUser();
    $orgA = membershipSeedOrg();
    $orgB = membershipSeedOrg();

    $repo->save(Membership::grant(MembershipId::generate(), $userId, $orgA->id, MembershipRole::OWNER));
    $repo->save(Membership::grant(MembershipId::generate(), $userId, $orgB->id, MembershipRole::STAFF));

    $list = $repo->findByUserId($userId);

    expect($list)->toHaveCount(2);
});

it('lists memberships by organization id', function (): void {
    $repo = new EloquentMembershipRepository;
    $org = membershipSeedOrg();
    $userA = membershipSeedUser();
    $userB = membershipSeedUser();
    $userC = membershipSeedUser();

    $repo->save(Membership::grant(MembershipId::generate(), $userA, $org->id, MembershipRole::OWNER));
    $repo->save(Membership::grant(MembershipId::generate(), $userB, $org->id, MembershipRole::ADMIN));
    $repo->save(Membership::grant(MembershipId::generate(), $userC, $org->id, MembershipRole::VIEWER));

    $list = $repo->findByOrganizationId($org->id);

    expect($list)->toHaveCount(3);
});

it('counts owners in organization', function (): void {
    $repo = new EloquentMembershipRepository;
    $org = membershipSeedOrg();
    $userA = membershipSeedUser();
    $userB = membershipSeedUser();
    $userC = membershipSeedUser();

    $repo->save(Membership::grant(MembershipId::generate(), $userA, $org->id, MembershipRole::OWNER));
    $repo->save(Membership::grant(MembershipId::generate(), $userB, $org->id, MembershipRole::OWNER));
    $repo->save(Membership::grant(MembershipId::generate(), $userC, $org->id, MembershipRole::ADMIN));

    expect($repo->countOwnersInOrganization($org->id))->toBe(2);
});

it('countOwnersInOrganization returns 0 for empty organization', function (): void {
    $repo = new EloquentMembershipRepository;
    $org = membershipSeedOrg();

    expect($repo->countOwnersInOrganization($org->id))->toBe(0);
});

it('deletes membership by id', function (): void {
    $repo = new EloquentMembershipRepository;
    $org = membershipSeedOrg();
    $userId = membershipSeedUser();

    $membership = Membership::grant(
        MembershipId::generate(),
        $userId,
        $org->id,
        MembershipRole::STAFF,
    );
    $repo->save($membership);

    $repo->delete($membership->id);

    expect($repo->findById($membership->id))->toBeNull();
});

it('delete is idempotent for unknown ids', function (): void {
    $repo = new EloquentMembershipRepository;

    $repo->delete(MembershipId::generate());

    expect(true)->toBeTrue();
});

it('updates role through save (updateOrCreate)', function (): void {
    $repo = new EloquentMembershipRepository;
    $org = membershipSeedOrg();
    $userId = membershipSeedUser();

    $membership = Membership::grant(MembershipId::generate(), $userId, $org->id, MembershipRole::STAFF);
    $repo->save($membership);

    $membership->changeRole(MembershipRole::ADMIN);
    $repo->save($membership);

    $found = $repo->findById($membership->id);
    expect($found->role())->toBe(MembershipRole::ADMIN);
});
