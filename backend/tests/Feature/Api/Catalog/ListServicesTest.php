<?php

declare(strict_types=1);

use App\Modules\Catalog\Domain\Entity\Service;
use App\Modules\Catalog\Domain\Repository\ServiceRepositoryInterface;
use App\Modules\Catalog\Domain\ValueObject\CategoryId;
use App\Modules\Catalog\Domain\ValueObject\Duration;
use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use App\Modules\Catalog\Domain\ValueObject\SubcategoryId;
use App\Modules\Catalog\Infrastructure\Persistence\Model\CategoryModel;
use App\Modules\Catalog\Infrastructure\Persistence\Model\SubcategoryModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function apiInsertCategory(string $name = 'API Beauty', int $sortOrder = 0): CategoryId
{
    $id = CategoryId::generate();
    CategoryModel::query()->insert([
        'id' => $id->toString(),
        'name' => $name,
        'slug' => strtolower(str_replace(' ', '-', $name)).'-'.substr($id->toString(), 0, 8),
        'sort_order' => $sortOrder,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return $id;
}

function apiInsertSubcategory(CategoryId $categoryId, string $name = 'API Hair'): SubcategoryId
{
    $id = SubcategoryId::generate();
    SubcategoryModel::query()->insert([
        'id' => $id->toString(),
        'category_id' => $categoryId->toString(),
        'name' => $name,
        'slug' => strtolower(str_replace(' ', '-', $name)).'-'.substr($id->toString(), 0, 8),
        'sort_order' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return $id;
}

function apiSaveTimeSlotService(
    string $name,
    CategoryId $categoryId,
    int $priceCents = 100000,
    ?SubcategoryId $subcategoryId = null,
    string $description = 'desc',
    int $duration = 60,
): Service {
    $service = Service::createTimeSlot(
        ServiceId::generate(),
        $name,
        $description,
        Money::fromCents($priceCents, 'RUB'),
        Duration::ofMinutes($duration),
        $categoryId,
        $subcategoryId,
    );
    app(ServiceRepositoryInterface::class)->save($service);

    return $service;
}

function apiSaveQuantityService(
    string $name,
    CategoryId $categoryId,
    int $priceCents = 500000,
    int $totalQuantity = 10,
): Service {
    $service = Service::createQuantity(
        ServiceId::generate(),
        $name,
        'desc',
        Money::fromCents($priceCents, 'RUB'),
        $totalQuantity,
        $categoryId,
        null,
    );
    app(ServiceRepositoryInterface::class)->save($service);

    return $service;
}

it('returns envelope with data array and meta', function (): void {
    $cat = apiInsertCategory('Стрижки');
    apiSaveTimeSlotService('Мужская стрижка', $cat);

    $response = $this->getJson('/api/v1/services');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data',
            'error',
            'meta' => ['page', 'per_page', 'total', 'last_page'],
        ])
        ->assertJson([
            'success' => true,
            'error' => null,
        ]);

    expect($response->json('data'))->toBeArray();
    expect($response->json('meta.page'))->toBe(1);
    expect($response->json('meta.per_page'))->toBe(20);
    expect($response->json('meta.total'))->toBe(1);
});

it('returns only active services', function (): void {
    $cat = apiInsertCategory('Стрижки');
    $active = apiSaveTimeSlotService('Активная', $cat);
    $inactive = apiSaveTimeSlotService('Неактивная', $cat);
    $inactive->deactivate();
    app(ServiceRepositoryInterface::class)->save($inactive);

    $response = $this->getJson('/api/v1/services');

    $response->assertStatus(200);
    $ids = collect($response->json('data'))->pluck('id')->all();
    expect($ids)->toContain($active->id()->toString());
    expect($ids)->not->toContain($inactive->id()->toString());
});

it('filters services by categoryId', function (): void {
    $cat1 = apiInsertCategory('One');
    $cat2 = apiInsertCategory('Two');
    $s1 = apiSaveTimeSlotService('In cat1', $cat1);
    $s2 = apiSaveTimeSlotService('In cat2', $cat2);

    $response = $this->getJson('/api/v1/services?categoryId='.$cat1->toString());

    $response->assertStatus(200);
    $ids = collect($response->json('data'))->pluck('id')->all();
    expect($ids)->toContain($s1->id()->toString());
    expect($ids)->not->toContain($s2->id()->toString());
});

it('filters services by type=time_slot', function (): void {
    $cat = apiInsertCategory('Mixed');
    $ts = apiSaveTimeSlotService('Time slot svc', $cat);
    $qty = apiSaveQuantityService('Quantity svc', $cat);

    $response = $this->getJson('/api/v1/services?type=time_slot');

    $response->assertStatus(200);
    $ids = collect($response->json('data'))->pluck('id')->all();
    expect($ids)->toContain($ts->id()->toString());
    expect($ids)->not->toContain($qty->id()->toString());
});

it('filters services by search query case-insensitive', function (): void {
    $cat = apiInsertCategory('Search');
    $matched = apiSaveTimeSlotService('Мужская стрижка', $cat);
    $other = apiSaveTimeSlotService('Массаж', $cat);

    $response = $this->getJson('/api/v1/services?search=стриж');

    $response->assertStatus(200);
    $ids = collect($response->json('data'))->pluck('id')->all();
    expect($ids)->toContain($matched->id()->toString());
    expect($ids)->not->toContain($other->id()->toString());
});

it('paginates with page and perPage', function (): void {
    $cat = apiInsertCategory('Paginated');
    for ($i = 1; $i <= 7; $i++) {
        apiSaveTimeSlotService("Service {$i}", $cat);
    }

    $response = $this->getJson('/api/v1/services?page=2&perPage=3');

    $response->assertStatus(200)
        ->assertJson([
            'meta' => [
                'page' => 2,
                'per_page' => 3,
                'total' => 7,
                'last_page' => 3,
            ],
        ]);
    expect($response->json('data'))->toHaveCount(3);
});

it('filters services by minPrice and maxPrice', function (): void {
    $cat = apiInsertCategory('Prices');
    $cheap = apiSaveTimeSlotService('Cheap', $cat, 100000);
    $mid = apiSaveTimeSlotService('Mid', $cat, 500000);
    $expensive = apiSaveTimeSlotService('Expensive', $cat, 1500000);

    $response = $this->getJson('/api/v1/services?minPrice=200000&maxPrice=1000000');

    $response->assertStatus(200);
    $ids = collect($response->json('data'))->pluck('id')->all();
    expect($ids)->toContain($mid->id()->toString());
    expect($ids)->not->toContain($cheap->id()->toString());
    expect($ids)->not->toContain($expensive->id()->toString());
});

it('validates invalid query params', function (): void {
    $response = $this->getJson('/api/v1/services?type=invalid');
    $response->assertStatus(422);
});
