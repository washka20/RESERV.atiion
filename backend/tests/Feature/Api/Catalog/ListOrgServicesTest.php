<?php

declare(strict_types=1);

use App\Modules\Catalog\Domain\Entity\Service;
use App\Modules\Catalog\Domain\Repository\ServiceRepositoryInterface;
use App\Modules\Catalog\Domain\ValueObject\CategoryId;
use App\Modules\Catalog\Domain\ValueObject\Duration;
use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use App\Modules\Catalog\Infrastructure\Persistence\Model\CategoryModel;
use App\Modules\Identity\Domain\ValueObject\MembershipRole;
use App\Modules\Identity\Domain\ValueObject\OrganizationId;
use App\Modules\Identity\Domain\ValueObject\UserId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function orgSvcInsertCategory(string $name = 'Beauty'): CategoryId
{
    $id = CategoryId::generate();
    CategoryModel::query()->insert([
        'id' => $id->toString(),
        'name' => $name,
        'slug' => strtolower(str_replace(' ', '-', $name)).'-'.substr($id->toString(), 0, 8),
        'sort_order' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return $id;
}

function orgSvcSaveTimeSlotService(string $name, CategoryId $categoryId, OrganizationId $orgId, int $price = 150000): Service
{
    $service = Service::createTimeSlot(
        ServiceId::generate(),
        $name,
        'desc',
        Money::fromCents($price, 'RUB'),
        Duration::ofMinutes(60),
        $categoryId,
        null,
        $orgId,
    );
    app(ServiceRepositoryInterface::class)->save($service);

    return $service;
}

it('GET /organizations/{slug}/services возвращает services организации', function (): void {
    $owner = identityInsertUser('owner-listsvc@test.com');
    $orgId = insertOrganizationForTests('my-salon');
    insertMembershipForTests(new UserId($owner->id), $orgId, MembershipRole::OWNER);

    $categoryId = orgSvcInsertCategory('Красота');
    orgSvcSaveTimeSlotService('Стрижка', $categoryId, $orgId, 150000);
    orgSvcSaveTimeSlotService('Укладка', $categoryId, $orgId, 200000);

    /** @var object{slug: string} $org */
    $org = DB::table('organizations')->select('slug')->where('id', $orgId->toString())->first();

    $response = $this->withHeaders(['Authorization' => 'Bearer '.identityIssueJwt($owner)])
        ->getJson("/api/v1/organizations/{$org->slug}/services");

    $response->assertStatus(200)
        ->assertJson(['success' => true])
        ->assertJsonStructure(['data' => [['id', 'name', 'price_amount', 'type']], 'meta' => ['total']]);

    expect($response->json('meta.total'))->toBe(2);
});

it('GET /organizations/{slug}/services — 403 для чужой org', function (): void {
    $outsider = identityInsertUser('outsider-listsvc@test.com');
    $orgId = insertOrganizationForTests('private-org');

    /** @var object{slug: string} $org */
    $org = DB::table('organizations')->select('slug')->where('id', $orgId->toString())->first();

    $response = $this->withHeaders(['Authorization' => 'Bearer '.identityIssueJwt($outsider)])
        ->getJson("/api/v1/organizations/{$org->slug}/services");

    $response->assertStatus(403);
});

it('GET /organizations/{slug}/services — 401 без auth', function (): void {
    $response = $this->getJson('/api/v1/organizations/any-org/services');
    $response->assertStatus(401);
});
