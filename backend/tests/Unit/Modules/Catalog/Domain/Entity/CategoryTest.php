<?php

declare(strict_types=1);

use App\Modules\Catalog\Domain\Entity\Category;
use App\Modules\Catalog\Domain\Entity\Subcategory;
use App\Modules\Catalog\Domain\Event\CategoryCreated;
use App\Modules\Catalog\Domain\ValueObject\CategoryId;
use App\Modules\Catalog\Domain\ValueObject\SubcategoryId;

it('creates a category with slug and sort order', function (): void {
    $id = CategoryId::generate();
    $category = Category::create($id, 'Beauty', 'beauty', 10);

    expect($category->id()->equals($id))->toBeTrue()
        ->and($category->name())->toBe('Beauty')
        ->and($category->slug())->toBe('beauty')
        ->and($category->sortOrder())->toBe(10)
        ->and($category->subcategories())->toBe([]);
});

it('emits CategoryCreated on create', function (): void {
    $category = Category::create(CategoryId::generate(), 'Beauty', 'beauty', 10);

    $events = $category->pullDomainEvents();

    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(CategoryCreated::class);
});

it('addSubcategory appends to subcategories', function (): void {
    $categoryId = CategoryId::generate();
    $category = Category::create($categoryId, 'Beauty', 'beauty', 10);
    $sub = Subcategory::create(SubcategoryId::generate(), $categoryId, 'Haircut', 'haircut', 1);

    $category->addSubcategory($sub);

    expect($category->subcategories())->toHaveCount(1);
});

it('addSubcategory throws on duplicate id', function (): void {
    $categoryId = CategoryId::generate();
    $category = Category::create($categoryId, 'Beauty', 'beauty', 10);
    $subId = SubcategoryId::generate();
    $sub1 = Subcategory::create($subId, $categoryId, 'Haircut', 'haircut', 1);
    $sub2 = Subcategory::create($subId, $categoryId, 'Other', 'other', 2);

    $category->addSubcategory($sub1);

    expect(fn () => $category->addSubcategory($sub2))->toThrow(InvalidArgumentException::class);
});

it('removeSubcategory removes by id', function (): void {
    $categoryId = CategoryId::generate();
    $category = Category::create($categoryId, 'Beauty', 'beauty', 10);
    $subId = SubcategoryId::generate();
    $sub = Subcategory::create($subId, $categoryId, 'Haircut', 'haircut', 1);
    $category->addSubcategory($sub);

    $category->removeSubcategory($subId);

    expect($category->subcategories())->toBe([]);
});

it('changeName updates name', function (): void {
    $category = Category::create(CategoryId::generate(), 'Beauty', 'beauty', 10);

    $category->changeName('Wellness');

    expect($category->name())->toBe('Wellness');
});

it('changeSortOrder updates sort order', function (): void {
    $category = Category::create(CategoryId::generate(), 'Beauty', 'beauty', 10);

    $category->changeSortOrder(99);

    expect($category->sortOrder())->toBe(99);
});

it('restore rebuilds category without events', function (): void {
    $id = CategoryId::generate();
    $category = Category::restore($id, 'Beauty', 'beauty', 10, []);

    expect($category->id()->equals($id))->toBeTrue()
        ->and($category->pullDomainEvents())->toBe([]);
});
