<?php

declare(strict_types=1);

use App\Modules\Identity\Domain\Entity\Membership;
use App\Modules\Identity\Domain\Entity\Organization;
use App\Modules\Identity\Domain\ValueObject\CancellationPolicy;
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

function makeOrgEntity(?string $slug = null): Organization
{
    return Organization::create(
        id: OrganizationId::generate(),
        slug: new OrganizationSlug($slug ?? 'org-'.bin2hex(random_bytes(4))),
        name: ['ru' => 'Тестовая', 'en' => 'Test'],
        description: ['ru' => 'Описание'],
        type: OrganizationType::SALON,
        city: 'Москва',
        phone: '+7-495-1234567',
        email: 'info@test.com',
    );
}

it('saves and retrieves organization by id', function (): void {
    $repo = new EloquentOrganizationRepository;
    $org = makeOrgEntity('salon-alpha');

    $repo->save($org);
    $found = $repo->findById($org->id);

    expect($found)->not->toBeNull();
    expect($found->id->toString())->toBe($org->id->toString());
    expect($found->slug->toString())->toBe('salon-alpha');
    expect($found->name('ru'))->toBe('Тестовая');
    expect($found->type)->toBe(OrganizationType::SALON);
});

it('returns null for unknown id', function (): void {
    $repo = new EloquentOrganizationRepository;

    $found = $repo->findById(OrganizationId::generate());

    expect($found)->toBeNull();
});

it('retrieves organization by slug', function (): void {
    $repo = new EloquentOrganizationRepository;
    $org = makeOrgEntity('salon-beta');
    $repo->save($org);

    $found = $repo->findBySlug(new OrganizationSlug('salon-beta'));

    expect($found)->not->toBeNull();
    expect($found->id->toString())->toBe($org->id->toString());
});

it('existsBySlug returns true for existing and false for missing', function (): void {
    $repo = new EloquentOrganizationRepository;
    $org = makeOrgEntity('salon-gamma');
    $repo->save($org);

    expect($repo->existsBySlug(new OrganizationSlug('salon-gamma')))->toBeTrue();
    expect($repo->existsBySlug(new OrganizationSlug('unknown-slug-xyz')))->toBeFalse();
});

it('updates organization through save (updateOrCreate)', function (): void {
    $repo = new EloquentOrganizationRepository;
    $org = makeOrgEntity('salon-delta');
    $repo->save($org);

    $org->verify();
    $org->changeCancellationPolicy(CancellationPolicy::STRICT);
    $repo->save($org);

    $found = $repo->findById($org->id);
    expect($found->isVerified())->toBeTrue();
    expect($found->cancellationPolicy())->toBe(CancellationPolicy::STRICT);
});

it('findByUserId returns orgs where user has membership', function (): void {
    $orgRepo = new EloquentOrganizationRepository;
    $memberRepo = new EloquentMembershipRepository;

    $orgA = makeOrgEntity('salon-epsilon');
    $orgB = makeOrgEntity('salon-zeta');
    $orgC = makeOrgEntity('salon-eta');
    $orgRepo->save($orgA);
    $orgRepo->save($orgB);
    $orgRepo->save($orgC);

    $user = UserModel::factory()->create();
    $userId = new UserId($user->id);

    $memberRepo->save(Membership::grant(
        MembershipId::generate(),
        $userId,
        $orgA->id,
        MembershipRole::OWNER,
    ));
    $memberRepo->save(Membership::grant(
        MembershipId::generate(),
        $userId,
        $orgB->id,
        MembershipRole::STAFF,
    ));

    $orgs = $orgRepo->findByUserId($userId);
    $ids = array_map(fn (Organization $o): string => $o->id->toString(), $orgs);

    expect($orgs)->toHaveCount(2);
    expect($ids)->toContain($orgA->id->toString());
    expect($ids)->toContain($orgB->id->toString());
    expect($ids)->not->toContain($orgC->id->toString());
});

it('findByUserId returns empty array when user has no memberships', function (): void {
    $repo = new EloquentOrganizationRepository;

    $orgs = $repo->findByUserId(UserId::generate());

    expect($orgs)->toBe([]);
});

it('preserves archived_at through save/load roundtrip', function (): void {
    $repo = new EloquentOrganizationRepository;
    $org = makeOrgEntity('salon-theta');
    $org->archive();
    $repo->save($org);

    $found = $repo->findById($org->id);

    expect($found->isArchived())->toBeTrue();
    expect($found->archivedAt())->not->toBeNull();
});
