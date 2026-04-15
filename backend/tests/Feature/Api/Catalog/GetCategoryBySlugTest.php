<?php

declare(strict_types=1);

use App\Modules\Catalog\Domain\ValueObject\CategoryId;
use App\Modules\Catalog\Domain\ValueObject\SubcategoryId;
use App\Modules\Catalog\Infrastructure\Persistence\Model\CategoryModel;
use App\Modules\Catalog\Infrastructure\Persistence\Model\SubcategoryModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns category with subcategories by slug', function (): void {
    $catId = CategoryId::generate();
    CategoryModel::query()->insert([
        'id' => $catId->toString(),
        'name' => 'Стрижки',
        'slug' => 'haircuts',
        'sort_order' => 10,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $subId = SubcategoryId::generate();
    SubcategoryModel::query()->insert([
        'id' => $subId->toString(),
        'category_id' => $catId->toString(),
        'name' => 'Мужские',
        'slug' => 'men-haircuts',
        'sort_order' => 10,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->getJson('/api/v1/categories/haircuts');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'name',
                'slug',
                'sort_order',
                'subcategories' => [
                    '*' => ['id', 'name', 'slug', 'sort_order'],
                ],
            ],
            'error',
            'meta',
        ])
        ->assertJson([
            'success' => true,
            'error' => null,
            'meta' => null,
            'data' => [
                'slug' => 'haircuts',
                'name' => 'Стрижки',
                'sort_order' => 10,
            ],
        ]);

    expect($response->json('data.subcategories'))->toHaveCount(1);
    expect($response->json('data.subcategories.0.slug'))->toBe('men-haircuts');
});

it('returns 404 envelope for nonexistent slug', function (): void {
    $response = $this->getJson('/api/v1/categories/nonexistent');

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
                'code' => 'CATEGORY_NOT_FOUND',
            ],
        ]);
});
