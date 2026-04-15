<?php

declare(strict_types=1);

use App\Modules\Catalog\Application\Command\DeleteCategory\DeleteCategoryCommand;
use App\Modules\Catalog\Application\Command\DeleteCategory\DeleteCategoryHandler;
use App\Modules\Catalog\Domain\Entity\Category;
use App\Modules\Catalog\Domain\Exception\CategoryHasServicesException;
use App\Modules\Catalog\Domain\Repository\CategoryRepositoryInterface;
use App\Modules\Catalog\Domain\Repository\ServiceRepositoryInterface;
use App\Modules\Catalog\Domain\ValueObject\CategoryId;
use Tests\Unit\Modules\Catalog\Application\Support\ServiceFactory;

it('deletes empty category', function (): void {
    $categories = Mockery::mock(CategoryRepositoryInterface::class);
    $services = Mockery::mock(ServiceRepositoryInterface::class);
    $id = CategoryId::generate();
    $categories->shouldReceive('findByIdOrFail')->once()
        ->andReturn(Category::restore($id, 'C', 'c', 0, []));
    $services->shouldReceive('findByCategory')->once()->andReturn([]);
    $categories->shouldReceive('delete')->once();

    $handler = new DeleteCategoryHandler($categories, $services);
    $handler->handle(new DeleteCategoryCommand($id->toString()));
});

it('throws CategoryHasServicesException when services attached', function (): void {
    $categories = Mockery::mock(CategoryRepositoryInterface::class);
    $services = Mockery::mock(ServiceRepositoryInterface::class);
    $id = CategoryId::generate();
    $categories->shouldReceive('findByIdOrFail')->once()
        ->andReturn(Category::restore($id, 'C', 'c', 0, []));
    $services->shouldReceive('findByCategory')->once()
        ->andReturn([ServiceFactory::timeSlot(null, $id)]);
    $categories->shouldNotReceive('delete');

    $handler = new DeleteCategoryHandler($categories, $services);
    $handler->handle(new DeleteCategoryCommand($id->toString()));
})->throws(CategoryHasServicesException::class);
