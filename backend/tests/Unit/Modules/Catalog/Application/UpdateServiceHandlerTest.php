<?php

declare(strict_types=1);

use App\Modules\Catalog\Application\Command\UpdateService\UpdateServiceCommand;
use App\Modules\Catalog\Application\Command\UpdateService\UpdateServiceHandler;
use App\Modules\Catalog\Domain\Event\ServiceUpdated;
use App\Modules\Catalog\Domain\Exception\ServiceNotFoundException;
use App\Modules\Catalog\Domain\Repository\ServiceRepositoryInterface;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use Tests\Unit\Modules\Catalog\Application\Support\RecordingEventDispatcher;
use Tests\Unit\Modules\Catalog\Application\Support\ServiceFactory;

it('updates service details and dispatches ServiceUpdated', function (): void {
    $services = Mockery::mock(ServiceRepositoryInterface::class);
    $dispatcher = new RecordingEventDispatcher;

    $id = ServiceId::generate();
    $service = ServiceFactory::timeSlot($id);
    $services->shouldReceive('findByIdOrFail')->once()->andReturn($service);
    $services->shouldReceive('save')->once();

    $handler = new UpdateServiceHandler($services, $dispatcher);
    $handler->handle(new UpdateServiceCommand(
        serviceId: $id->toString(),
        name: 'New',
        description: 'New desc',
        priceAmount: 200000,
        priceCurrency: 'RUB',
    ));

    expect($service->name())->toBe('New');
    expect($service->price()->amount())->toBe(200000);
    expect($dispatcher->events)->toHaveCount(1);
    expect($dispatcher->events[0])->toBeInstanceOf(ServiceUpdated::class);
});

it('throws ServiceNotFoundException if service missing', function (): void {
    $services = Mockery::mock(ServiceRepositoryInterface::class);
    $id = ServiceId::generate();
    $services->shouldReceive('findByIdOrFail')->once()
        ->andThrow(ServiceNotFoundException::byId($id));

    $handler = new UpdateServiceHandler($services, new RecordingEventDispatcher);
    $handler->handle(new UpdateServiceCommand($id->toString(), 'n', 'd', 100, 'RUB'));
})->throws(ServiceNotFoundException::class);
