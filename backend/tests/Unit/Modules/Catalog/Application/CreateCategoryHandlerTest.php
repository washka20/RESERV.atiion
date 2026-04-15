<?php

declare(strict_types=1);

use App\Modules\Catalog\Application\Command\CreateCategory\CreateCategoryCommand;
use App\Modules\Catalog\Application\Command\CreateCategory\CreateCategoryHandler;
use App\Modules\Catalog\Domain\Entity\Category;
use App\Modules\Catalog\Domain\Event\CategoryCreated;
use App\Modules\Catalog\Domain\Repository\CategoryRepositoryInterface;
use Tests\Unit\Modules\Catalog\Application\Support\RecordingEventDispatcher;

it('creates category and dispatches CategoryCreated', function (): void {
    $categories = Mockery::mock(CategoryRepositoryInterface::class);
    $dispatcher = new RecordingEventDispatcher;
    $categories->shouldReceive('save')->once()->with(Mockery::type(Category::class));

    $handler = new CreateCategoryHandler($categories, $dispatcher);
    $id = $handler->handle(new CreateCategoryCommand('Beauty', 'beauty', 5));

    expect($id)->toMatch('/^[0-9a-f-]{36}$/');
    expect($dispatcher->events)->toHaveCount(1);
    expect($dispatcher->events[0])->toBeInstanceOf(CategoryCreated::class);
});
