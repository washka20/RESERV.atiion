<?php

declare(strict_types=1);

use App\Modules\Catalog\Application\Query\GetCategoryBySlug\GetCategoryBySlugHandler;
use App\Modules\Catalog\Application\Query\GetCategoryBySlug\GetCategoryBySlugQuery;
use App\Modules\Catalog\Infrastructure\Persistence\Model\CategoryModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns category with subcategories by slug', function (): void {
    $cat = insertCategory('Beauty');
    insertSubcategory($cat, 'Hair');
    insertSubcategory($cat, 'Nails');

    $slug = CategoryModel::query()->where('id', $cat->toString())->value('slug');

    $result = app(GetCategoryBySlugHandler::class)->handle(new GetCategoryBySlugQuery($slug));

    expect($result)->not->toBeNull();
    expect($result->category->slug)->toBe($slug);
    expect($result->category->name)->toBe('Beauty');
    expect($result->subcategories)->toHaveCount(2);
});

it('returns null when slug not found', function (): void {
    $result = app(GetCategoryBySlugHandler::class)->handle(new GetCategoryBySlugQuery('nonexistent-slug'));

    expect($result)->toBeNull();
});

it('includes only subcategories of matched category', function (): void {
    $catA = insertCategory('Alpha');
    $catB = insertCategory('Beta');
    insertSubcategory($catA, 'SubAlpha1');
    insertSubcategory($catA, 'SubAlpha2');
    insertSubcategory($catB, 'SubBeta');

    $slugA = CategoryModel::query()->where('id', $catA->toString())->value('slug');

    $result = app(GetCategoryBySlugHandler::class)->handle(new GetCategoryBySlugQuery($slugA));

    expect($result)->not->toBeNull();
    expect($result->subcategories)->toHaveCount(2);
    $names = array_map(static fn ($s) => $s->name, $result->subcategories);
    sort($names);
    expect($names)->toBe(['SubAlpha1', 'SubAlpha2']);
});
