<?php

declare(strict_types=1);

use App\Modules\Catalog\Application\Command\CreateSubcategory\CreateSubcategoryCommand;
use App\Modules\Catalog\Application\Command\CreateSubcategory\CreateSubcategoryHandler;
use App\Modules\Catalog\Domain\Entity\Category;
use App\Modules\Catalog\Domain\Exception\CategoryNotFoundException;
use App\Modules\Catalog\Domain\Repository\CategoryRepositoryInterface;
use App\Modules\Catalog\Domain\ValueObject\CategoryId;
use Tests\Unit\Modules\Catalog\Application\Support\RecordingEventDispatcher;

it('adds subcategory to category and saves', function (): void {
    $categories = Mockery::mock(CategoryRepositoryInterface::class);
    $id = CategoryId::generate();
    $category = Category::restore($id, 'C', 'c', 0, []);
    $categories->shouldReceive('findByIdOrFail')->once()->andReturn($category);
    $categories->shouldReceive('save')->once();

    $handler = new CreateSubcategoryHandler($categories, new RecordingEventDispatcher);
    $subId = $handler->handle(new CreateSubcategoryCommand($id->toString(), 'Sub', 'sub', 1));

    expect($subId)->toMatch('/^[0-9a-f-]{36}$/');
    expect($category->subcategories())->toHaveCount(1);
    expect($category->subcategories()[0]->name())->toBe('Sub');
});

it('throws if parent category missing', function (): void {
    $categories = Mockery::mock(CategoryRepositoryInterface::class);
    $id = CategoryId::generate();
    $categories->shouldReceive('findByIdOrFail')->once()
        ->andThrow(CategoryNotFoundException::byId($id));

    $handler = new CreateSubcategoryHandler($categories, new RecordingEventDispatcher);
    $handler->handle(new CreateSubcategoryCommand($id->toString(), 'n', 's', 0));
})->throws(CategoryNotFoundException::class);
