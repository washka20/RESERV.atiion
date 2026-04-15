<?php

declare(strict_types=1);

use App\Modules\Catalog\Domain\Entity\Category;
use App\Modules\Catalog\Domain\Entity\Subcategory;
use App\Modules\Catalog\Domain\Exception\CategoryNotFoundException;
use App\Modules\Catalog\Domain\Repository\CategoryRepositoryInterface;
use App\Modules\Catalog\Domain\ValueObject\CategoryId;
use App\Modules\Catalog\Domain\ValueObject\SubcategoryId;
use App\Modules\Catalog\Infrastructure\Persistence\Model\SubcategoryModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function categoryRepo(): CategoryRepositoryInterface
{
    return app(CategoryRepositoryInterface::class);
}

it('saves and finds a category by id', function (): void {
    $category = Category::create(CategoryId::generate(), 'Beauty', 'beauty', 10);

    categoryRepo()->save($category);

    $found = categoryRepo()->findById($category->id());
    expect($found)->not->toBeNull();
    expect($found->name())->toBe('Beauty');
    expect($found->slug())->toBe('beauty');
    expect($found->sortOrder())->toBe(10);
    expect($found->subcategories())->toBe([]);
});

it('saves category with subcategories', function (): void {
    $categoryId = CategoryId::generate();
    $sub1 = Subcategory::create(SubcategoryId::generate(), $categoryId, 'Hair', 'hair', 0);
    $sub2 = Subcategory::create(SubcategoryId::generate(), $categoryId, 'Nails', 'nails', 1);

    $category = Category::restore($categoryId, 'Beauty', 'beauty', 0, [$sub1, $sub2]);

    categoryRepo()->save($category);

    $found = categoryRepo()->findById($categoryId);
    expect($found->subcategories())->toHaveCount(2);
    $names = array_map(static fn (Subcategory $s) => $s->name(), $found->subcategories());
    expect($names)->toBe(['Hair', 'Nails']);
});

it('removes subcategories missing on re-save', function (): void {
    $categoryId = CategoryId::generate();
    $sub1 = Subcategory::create(SubcategoryId::generate(), $categoryId, 'Hair', 'hair', 0);
    $sub2 = Subcategory::create(SubcategoryId::generate(), $categoryId, 'Nails', 'nails', 1);

    $category = Category::restore($categoryId, 'Beauty', 'beauty', 0, [$sub1, $sub2]);
    categoryRepo()->save($category);

    $category->removeSubcategory($sub1->id());
    categoryRepo()->save($category);

    $found = categoryRepo()->findById($categoryId);
    expect($found->subcategories())->toHaveCount(1);
    expect($found->subcategories()[0]->name())->toBe('Nails');
    expect(SubcategoryModel::query()->where('category_id', $categoryId->toString())->count())->toBe(1);
});

it('finds category by slug', function (): void {
    $category = Category::create(CategoryId::generate(), 'Sport', 'sport', 5);
    categoryRepo()->save($category);

    $found = categoryRepo()->findBySlug('sport');
    expect($found)->not->toBeNull();
    expect($found->id()->equals($category->id()))->toBeTrue();
});

it('returns null when slug not found', function (): void {
    expect(categoryRepo()->findBySlug('nonexistent'))->toBeNull();
});

it('findByIdOrFail throws when category missing', function (): void {
    expect(fn () => categoryRepo()->findByIdOrFail(CategoryId::generate()))
        ->toThrow(CategoryNotFoundException::class);
});

it('all() returns categories sorted by sort_order', function (): void {
    $b = Category::create(CategoryId::generate(), 'B', 'b', 20);
    $a = Category::create(CategoryId::generate(), 'A', 'a', 10);
    $c = Category::create(CategoryId::generate(), 'C', 'c', 30);

    categoryRepo()->save($b);
    categoryRepo()->save($a);
    categoryRepo()->save($c);

    $all = categoryRepo()->all();
    $names = array_map(static fn (Category $cat) => $cat->name(), $all);
    expect($names)->toBe(['A', 'B', 'C']);
});

it('deletes category', function (): void {
    $category = Category::create(CategoryId::generate(), 'ToDelete', 'to-delete', 0);
    categoryRepo()->save($category);

    categoryRepo()->delete($category->id());

    expect(categoryRepo()->findById($category->id()))->toBeNull();
});
