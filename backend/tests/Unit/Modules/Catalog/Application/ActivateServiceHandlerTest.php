<?php

declare(strict_types=1);

use App\Modules\Catalog\Application\Command\ActivateService\ActivateServiceCommand;
use App\Modules\Catalog\Application\Command\ActivateService\ActivateServiceHandler;
use App\Modules\Catalog\Domain\Event\ServiceActivated;
use App\Modules\Catalog\Domain\Repository\ServiceRepositoryInterface;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use Tests\Unit\Modules\Catalog\Application\Support\RecordingEventDispatcher;
use Tests\Unit\Modules\Catalog\Application\Support\ServiceFactory;

it('activates previously deactivated service and dispatches event', function (): void {
    $services = Mockery::mock(ServiceRepositoryInterface::class);
    $dispatcher = new RecordingEventDispatcher;
    $id = ServiceId::generate();
    $service = ServiceFactory::timeSlot($id);
    $service->deactivate();
    $service->pullDomainEvents();
    $services->shouldReceive('findByIdOrFail')->once()->andReturn($service);
    $services->shouldReceive('save')->once();

    $handler = new ActivateServiceHandler($services, $dispatcher);
    $handler->handle(new ActivateServiceCommand($id->toString()));

    expect($dispatcher->events)->toHaveCount(1);
    expect($dispatcher->events[0])->toBeInstanceOf(ServiceActivated::class);
    expect($service->isActive())->toBeTrue();
});

it('is idempotent when service already active', function (): void {
    $services = Mockery::mock(ServiceRepositoryInterface::class);
    $dispatcher = new RecordingEventDispatcher;
    $id = ServiceId::generate();
    $services->shouldReceive('findByIdOrFail')->once()->andReturn(ServiceFactory::timeSlot($id));
    $services->shouldReceive('save')->once();

    $handler = new ActivateServiceHandler($services, $dispatcher);
    $handler->handle(new ActivateServiceCommand($id->toString()));

    expect($dispatcher->events)->toBeEmpty();
});
