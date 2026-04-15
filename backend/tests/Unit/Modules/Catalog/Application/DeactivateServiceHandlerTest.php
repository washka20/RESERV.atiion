<?php

declare(strict_types=1);

use App\Modules\Catalog\Application\Command\DeactivateService\DeactivateServiceCommand;
use App\Modules\Catalog\Application\Command\DeactivateService\DeactivateServiceHandler;
use App\Modules\Catalog\Domain\Event\ServiceDeactivated;
use App\Modules\Catalog\Domain\Repository\ServiceRepositoryInterface;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use Tests\Unit\Modules\Catalog\Application\Support\RecordingEventDispatcher;
use Tests\Unit\Modules\Catalog\Application\Support\ServiceFactory;

it('deactivates service and dispatches event', function (): void {
    $services = Mockery::mock(ServiceRepositoryInterface::class);
    $dispatcher = new RecordingEventDispatcher;
    $id = ServiceId::generate();
    $services->shouldReceive('findByIdOrFail')->once()->andReturn(ServiceFactory::timeSlot($id));
    $services->shouldReceive('save')->once();

    $handler = new DeactivateServiceHandler($services, $dispatcher);
    $handler->handle(new DeactivateServiceCommand($id->toString()));

    expect($dispatcher->events)->toHaveCount(1);
    expect($dispatcher->events[0])->toBeInstanceOf(ServiceDeactivated::class);
});
