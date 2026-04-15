<?php

declare(strict_types=1);

use App\Modules\Catalog\Domain\ValueObject\CategoryId;
use App\Modules\Catalog\Domain\ValueObject\SubcategoryId;
use App\Modules\Catalog\Infrastructure\Persistence\Model\CategoryModel;
use App\Modules\Catalog\Infrastructure\Persistence\Model\SubcategoryModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns categories list with nested subcategories', function (): void {
    $cat1Id = CategoryId::generate();
    CategoryModel::query()->insert([
        'id' => $cat1Id->toString(),
        'name' => 'Стрижки',
        'slug' => 'haircuts',
        'sort_order' => 10,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $cat2Id = CategoryId::generate();
    CategoryModel::query()->insert([
        'id' => $cat2Id->toString(),
        'name' => 'Отели',
        'slug' => 'hotels',
        'sort_order' => 20,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $subId = SubcategoryId::generate();
    SubcategoryModel::query()->insert([
        'id' => $subId->toString(),
        'category_id' => $cat1Id->toString(),
        'name' => 'Мужские',
        'slug' => 'men-haircuts',
        'sort_order' => 10,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->getJson('/api/v1/categories');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => [
                '*' => ['id', 'name', 'slug', 'sort_order', 'subcategories'],
            ],
            'error',
            'meta',
        ])
        ->assertJson([
            'success' => true,
            'error' => null,
            'meta' => null,
        ]);

    $data = $response->json('data');
    expect($data)->toHaveCount(2);
    expect($data[0]['slug'])->toBe('haircuts');
    expect($data[0]['subcategories'])->toHaveCount(1);
    expect($data[0]['subcategories'][0]['slug'])->toBe('men-haircuts');
    expect($data[1]['slug'])->toBe('hotels');
    expect($data[1]['subcategories'])->toBe([]);
});

it('returns empty array when no categories', function (): void {
    $response = $this->getJson('/api/v1/categories');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [],
            'error' => null,
            'meta' => null,
        ]);
});
