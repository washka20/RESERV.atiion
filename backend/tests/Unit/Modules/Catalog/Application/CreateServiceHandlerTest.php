<?php

declare(strict_types=1);

use App\Modules\Catalog\Application\Command\CreateService\CreateServiceCommand;
use App\Modules\Catalog\Application\Command\CreateService\CreateServiceHandler;
use App\Modules\Catalog\Domain\Entity\Category;
use App\Modules\Catalog\Domain\Entity\Service;
use App\Modules\Catalog\Domain\Event\ServiceCreated;
use App\Modules\Catalog\Domain\Exception\CategoryNotFoundException;
use App\Modules\Catalog\Domain\Repository\CategoryRepositoryInterface;
use App\Modules\Catalog\Domain\Repository\ServiceRepositoryInterface;
use App\Modules\Catalog\Domain\ValueObject\CategoryId;
use App\Modules\Catalog\Domain\ValueObject\ServiceType;
use Tests\Unit\Modules\Catalog\Application\Support\RecordingEventDispatcher;

function makeCategory(?CategoryId $id = null): Category
{
    return Category::restore(
        $id ?? CategoryId::generate(),
        'Beauty',
        'beauty',
        0,
        [],
    );
}

it('creates TIME_SLOT service and dispatches ServiceCreated', function (): void {
    $services = Mockery::mock(ServiceRepositoryInterface::class);
    $categories = Mockery::mock(CategoryRepositoryInterface::class);
    $dispatcher = new RecordingEventDispatcher;

    $categoryId = CategoryId::generate();
    $categories->shouldReceive('findByIdOrFail')->once()->andReturn(makeCategory($categoryId));
    $services->shouldReceive('save')->once()->with(Mockery::type(Service::class));

    $handler = new CreateServiceHandler($services, $categories, $dispatcher);
    $id = $handler->handle(new CreateServiceCommand(
        name: 'Haircut',
        description: 'Classic cut',
        priceAmount: 150000,
        priceCurrency: 'RUB',
        type: 'time_slot',
        categoryId: $categoryId->toString(),
        durationMinutes: 60,
    ));

    expect($id)->toMatch('/^[0-9a-f-]{36}$/');
    expect($dispatcher->events)->toHaveCount(1);
    expect($dispatcher->events[0])->toBeInstanceOf(ServiceCreated::class);
    expect($dispatcher->events[0]->type())->toBe(ServiceType::TIME_SLOT);
});

it('creates QUANTITY service', function (): void {
    $services = Mockery::mock(ServiceRepositoryInterface::class);
    $categories = Mockery::mock(CategoryRepositoryInterface::class);
    $dispatcher = new RecordingEventDispatcher;

    $categoryId = CategoryId::generate();
    $categories->shouldReceive('findByIdOrFail')->once()->andReturn(makeCategory($categoryId));
    $services->shouldReceive('save')->once();

    $handler = new CreateServiceHandler($services, $categories, $dispatcher);
    $handler->handle(new CreateServiceCommand(
        name: 'Tent',
        description: 'Rent',
        priceAmount: 500000,
        priceCurrency: 'RUB',
        type: 'quantity',
        categoryId: $categoryId->toString(),
        totalQuantity: 10,
    ));

    expect($dispatcher->events[0])->toBeInstanceOf(ServiceCreated::class);
    expect($dispatcher->events[0]->type())->toBe(ServiceType::QUANTITY);
});

it('throws if category missing', function (): void {
    $services = Mockery::mock(ServiceRepositoryInterface::class);
    $categories = Mockery::mock(CategoryRepositoryInterface::class);
    $categoryId = CategoryId::generate();
    $categories->shouldReceive('findByIdOrFail')->once()
        ->andThrow(CategoryNotFoundException::byId($categoryId));

    $handler = new CreateServiceHandler($services, $categories, new RecordingEventDispatcher);
    $handler->handle(new CreateServiceCommand(
        'n', 'd', 100, 'RUB', 'time_slot', $categoryId->toString(), durationMinutes: 30,
    ));
})->throws(CategoryNotFoundException::class);

it('throws if TIME_SLOT without duration', function (): void {
    $services = Mockery::mock(ServiceRepositoryInterface::class);
    $categories = Mockery::mock(CategoryRepositoryInterface::class);
    $categoryId = CategoryId::generate();
    $categories->shouldReceive('findByIdOrFail')->once()->andReturn(makeCategory($categoryId));

    $handler = new CreateServiceHandler($services, $categories, new RecordingEventDispatcher);
    $handler->handle(new CreateServiceCommand(
        'n', 'd', 100, 'RUB', 'time_slot', $categoryId->toString(),
    ));
})->throws(InvalidArgumentException::class);

it('throws if QUANTITY without totalQuantity', function (): void {
    $services = Mockery::mock(ServiceRepositoryInterface::class);
    $categories = Mockery::mock(CategoryRepositoryInterface::class);
    $categoryId = CategoryId::generate();
    $categories->shouldReceive('findByIdOrFail')->once()->andReturn(makeCategory($categoryId));

    $handler = new CreateServiceHandler($services, $categories, new RecordingEventDispatcher);
    $handler->handle(new CreateServiceCommand(
        'n', 'd', 100, 'RUB', 'quantity', $categoryId->toString(),
    ));
})->throws(InvalidArgumentException::class);
