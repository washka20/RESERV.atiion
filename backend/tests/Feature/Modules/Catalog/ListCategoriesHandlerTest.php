<?php

declare(strict_types=1);

use App\Modules\Catalog\Application\Query\ListCategories\ListCategoriesHandler;
use App\Modules\Catalog\Application\Query\ListCategories\ListCategoriesQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns categories with nested subcategories sorted by sort_order', function (): void {
    $catB = insertCategory('Beta', sortOrder: 20);
    $catA = insertCategory('Alpha', sortOrder: 10);
    insertSubcategory($catA, 'Hair');
    insertSubcategory($catA, 'Nails');

    $result = app(ListCategoriesHandler::class)->handle(new ListCategoriesQuery);

    expect($result)->toHaveCount(2);
    expect($result[0]->category->sortOrder)->toBe(10);
    expect($result[0]->category->name)->toBe('Alpha');
    expect($result[0]->subcategories)->toHaveCount(2);
    expect($result[1]->category->name)->toBe('Beta');
    expect($result[1]->subcategories)->toHaveCount(0);
});

it('returns empty array when no categories exist', function (): void {
    $result = app(ListCategoriesHandler::class)->handle(new ListCategoriesQuery);

    expect($result)->toBe([]);
});

it('subcategory DTO contains correct data', function (): void {
    $cat = insertCategory('Beauty');
    $sub = insertSubcategory($cat, 'Hair');

    $result = app(ListCategoriesHandler::class)->handle(new ListCategoriesQuery);

    $subDto = $result[0]->subcategories[0];
    expect($subDto->id)->toBe($sub->toString());
    expect($subDto->categoryId)->toBe($cat->toString());
    expect($subDto->name)->toBe('Hair');
});
