<?php

declare(strict_types=1);

use App\Modules\Catalog\Application\Command\UpdateCategory\UpdateCategoryCommand;
use App\Modules\Catalog\Application\Command\UpdateCategory\UpdateCategoryHandler;
use App\Modules\Catalog\Domain\Entity\Category;
use App\Modules\Catalog\Domain\Exception\CategoryNotFoundException;
use App\Modules\Catalog\Domain\Repository\CategoryRepositoryInterface;
use App\Modules\Catalog\Domain\ValueObject\CategoryId;
use Tests\Unit\Modules\Catalog\Application\Support\RecordingEventDispatcher;

it('updates category name and sortOrder', function (): void {
    $categories = Mockery::mock(CategoryRepositoryInterface::class);
    $id = CategoryId::generate();
    $category = Category::restore($id, 'Old', 'old', 0, []);
    $categories->shouldReceive('findByIdOrFail')->once()->andReturn($category);
    $categories->shouldReceive('save')->once();

    $handler = new UpdateCategoryHandler($categories, new RecordingEventDispatcher);
    $handler->handle(new UpdateCategoryCommand($id->toString(), 'New', 10));

    expect($category->name())->toBe('New');
    expect($category->sortOrder())->toBe(10);
});

it('throws if category missing', function (): void {
    $categories = Mockery::mock(CategoryRepositoryInterface::class);
    $id = CategoryId::generate();
    $categories->shouldReceive('findByIdOrFail')->once()
        ->andThrow(CategoryNotFoundException::byId($id));

    $handler = new UpdateCategoryHandler($categories, new RecordingEventDispatcher);
    $handler->handle(new UpdateCategoryCommand($id->toString(), 'n', 0));
})->throws(CategoryNotFoundException::class);
