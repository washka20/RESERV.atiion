<?php

declare(strict_types=1);

use App\Modules\Catalog\Application\Command\UpdateSubcategory\UpdateSubcategoryCommand;
use App\Modules\Catalog\Application\Command\UpdateSubcategory\UpdateSubcategoryHandler;
use App\Modules\Catalog\Domain\Entity\Category;
use App\Modules\Catalog\Domain\Entity\Subcategory;
use App\Modules\Catalog\Domain\Repository\CategoryRepositoryInterface;
use App\Modules\Catalog\Domain\ValueObject\CategoryId;
use App\Modules\Catalog\Domain\ValueObject\SubcategoryId;
use Tests\Unit\Modules\Catalog\Application\Support\RecordingEventDispatcher;

it('updates subcategory name and sortOrder', function (): void {
    $categories = Mockery::mock(CategoryRepositoryInterface::class);
    $catId = CategoryId::generate();
    $subId = SubcategoryId::generate();
    $sub = Subcategory::restore($subId, $catId, 'Old', 'old', 0);
    $category = Category::restore($catId, 'C', 'c', 0, [$sub]);
    $categories->shouldReceive('findByIdOrFail')->once()->andReturn($category);
    $categories->shouldReceive('save')->once();

    $handler = new UpdateSubcategoryHandler($categories, new RecordingEventDispatcher);
    $handler->handle(new UpdateSubcategoryCommand($catId->toString(), $subId->toString(), 'New', 10));

    expect($sub->name())->toBe('New');
    expect($sub->sortOrder())->toBe(10);
});

it('throws if subcategory missing in category', function (): void {
    $categories = Mockery::mock(CategoryRepositoryInterface::class);
    $catId = CategoryId::generate();
    $category = Category::restore($catId, 'C', 'c', 0, []);
    $categories->shouldReceive('findByIdOrFail')->once()->andReturn($category);
    $categories->shouldNotReceive('save');

    $handler = new UpdateSubcategoryHandler($categories, new RecordingEventDispatcher);
    $handler->handle(new UpdateSubcategoryCommand($catId->toString(), SubcategoryId::generate()->toString(), 'n', 0));
})->throws(InvalidArgumentException::class);
