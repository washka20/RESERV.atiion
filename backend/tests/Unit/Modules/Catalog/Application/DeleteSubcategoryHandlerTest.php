<?php

declare(strict_types=1);

use App\Modules\Catalog\Application\Command\DeleteSubcategory\DeleteSubcategoryCommand;
use App\Modules\Catalog\Application\Command\DeleteSubcategory\DeleteSubcategoryHandler;
use App\Modules\Catalog\Domain\Entity\Category;
use App\Modules\Catalog\Domain\Entity\Subcategory;
use App\Modules\Catalog\Domain\Repository\CategoryRepositoryInterface;
use App\Modules\Catalog\Domain\ValueObject\CategoryId;
use App\Modules\Catalog\Domain\ValueObject\SubcategoryId;
use Tests\Unit\Modules\Catalog\Application\Support\RecordingEventDispatcher;

it('removes subcategory and saves', function (): void {
    $categories = Mockery::mock(CategoryRepositoryInterface::class);
    $catId = CategoryId::generate();
    $subId = SubcategoryId::generate();
    $sub = Subcategory::restore($subId, $catId, 'S', 's', 0);
    $category = Category::restore($catId, 'C', 'c', 0, [$sub]);
    $categories->shouldReceive('findByIdOrFail')->once()->andReturn($category);
    $categories->shouldReceive('save')->once();

    $handler = new DeleteSubcategoryHandler($categories, new RecordingEventDispatcher);
    $handler->handle(new DeleteSubcategoryCommand($catId->toString(), $subId->toString()));

    expect($category->subcategories())->toBeEmpty();
});

it('is idempotent when subcategory missing', function (): void {
    $categories = Mockery::mock(CategoryRepositoryInterface::class);
    $catId = CategoryId::generate();
    $category = Category::restore($catId, 'C', 'c', 0, []);
    $categories->shouldReceive('findByIdOrFail')->once()->andReturn($category);
    $categories->shouldReceive('save')->once();

    $handler = new DeleteSubcategoryHandler($categories, new RecordingEventDispatcher);
    $handler->handle(new DeleteSubcategoryCommand($catId->toString(), SubcategoryId::generate()->toString()));
});
