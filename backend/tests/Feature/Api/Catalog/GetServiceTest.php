<?php

declare(strict_types=1);

use App\Modules\Catalog\Domain\Entity\Service;
use App\Modules\Catalog\Domain\Repository\ServiceRepositoryInterface;
use App\Modules\Catalog\Domain\ValueObject\CategoryId;
use App\Modules\Catalog\Domain\ValueObject\Duration;
use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use App\Modules\Catalog\Infrastructure\Persistence\Model\CategoryModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns full ServiceDTO envelope for existing service', function (): void {
    $categoryId = CategoryId::generate();
    CategoryModel::query()->insert([
        'id' => $categoryId->toString(),
        'name' => 'Cat',
        'slug' => 'cat-'.substr($categoryId->toString(), 0, 8),
        'sort_order' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $service = Service::createTimeSlot(
        ServiceId::generate(),
        'My service',
        'Full description',
        Money::fromCents(250000, 'RUB'),
        Duration::ofMinutes(45),
        $categoryId,
        null,
    );
    app(ServiceRepositoryInterface::class)->save($service);

    $response = $this->getJson('/api/v1/services/'.$service->id()->toString());

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'name',
                'description',
                'price_amount',
                'price_currency',
                'type',
                'duration_minutes',
                'total_quantity',
                'category_id',
                'category_name',
                'subcategory_id',
                'subcategory_name',
                'is_active',
                'images',
                'created_at',
                'updated_at',
            ],
            'error',
            'meta',
        ])
        ->assertJson([
            'success' => true,
            'error' => null,
            'meta' => null,
            'data' => [
                'id' => $service->id()->toString(),
                'name' => 'My service',
                'description' => 'Full description',
                'price_amount' => 250000,
                'price_currency' => 'RUB',
                'type' => 'time_slot',
                'duration_minutes' => 45,
                'total_quantity' => null,
                'category_id' => $categoryId->toString(),
                'category_name' => 'Cat',
                'is_active' => true,
            ],
        ]);
});

it('returns 404 envelope for nonexistent service', function (): void {
    $nonexistent = '00000000-0000-4000-8000-000000000000';

    $response = $this->getJson('/api/v1/services/'.$nonexistent);

    $response->assertStatus(404)
        ->assertJsonStructure([
            'success',
            'data',
            'error' => ['code', 'message', 'details'],
            'meta',
        ])
        ->assertJson([
            'success' => false,
            'data' => null,
            'meta' => null,
            'error' => [
                'code' => 'SERVICE_NOT_FOUND',
            ],
        ]);
});

it('returns 404 for non-uuid id (route constraint)', function (): void {
    $response = $this->getJson('/api/v1/services/not-a-uuid');
    $response->assertStatus(404);
});
