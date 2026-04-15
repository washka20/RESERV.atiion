<?php

declare(strict_types=1);

use App\Modules\Catalog\Domain\Entity\Subcategory;
use App\Modules\Catalog\Domain\ValueObject\CategoryId;
use App\Modules\Catalog\Domain\ValueObject\SubcategoryId;

it('creates a subcategory with parent category id', function (): void {
    $subId = SubcategoryId::generate();
    $categoryId = CategoryId::generate();

    $sub = Subcategory::create($subId, $categoryId, 'Haircut', 'haircut', 1);

    expect($sub->id()->equals($subId))->toBeTrue()
        ->and($sub->categoryId()->equals($categoryId))->toBeTrue()
        ->and($sub->name())->toBe('Haircut')
        ->and($sub->slug())->toBe('haircut')
        ->and($sub->sortOrder())->toBe(1);
});

it('changeName updates name', function (): void {
    $sub = Subcategory::create(SubcategoryId::generate(), CategoryId::generate(), 'Haircut', 'haircut', 1);

    $sub->changeName('Trim');

    expect($sub->name())->toBe('Trim');
});

it('changeSortOrder updates sort order', function (): void {
    $sub = Subcategory::create(SubcategoryId::generate(), CategoryId::generate(), 'Haircut', 'haircut', 1);

    $sub->changeSortOrder(42);

    expect($sub->sortOrder())->toBe(42);
});
