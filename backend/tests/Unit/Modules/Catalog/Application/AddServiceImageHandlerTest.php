<?php

declare(strict_types=1);

use App\Modules\Catalog\Application\Command\AddServiceImage\AddServiceImageCommand;
use App\Modules\Catalog\Application\Command\AddServiceImage\AddServiceImageHandler;
use App\Modules\Catalog\Domain\Repository\ServiceRepositoryInterface;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use Tests\Unit\Modules\Catalog\Application\Support\RecordingEventDispatcher;
use Tests\Unit\Modules\Catalog\Application\Support\ServiceFactory;

it('adds image to service and saves', function (): void {
    $services = Mockery::mock(ServiceRepositoryInterface::class);
    $id = ServiceId::generate();
    $service = ServiceFactory::timeSlot($id);
    $services->shouldReceive('findByIdOrFail')->once()->andReturn($service);
    $services->shouldReceive('save')->once();

    $handler = new AddServiceImageHandler($services, new RecordingEventDispatcher);
    $handler->handle(new AddServiceImageCommand($id->toString(), 'services/img.jpg'));

    expect($service->images())->toHaveCount(1);
    expect($service->images()[0]->value())->toBe('services/img.jpg');
});

it('rejects invalid path via ImagePath VO', function (): void {
    $services = Mockery::mock(ServiceRepositoryInterface::class);
    $services->shouldReceive('findByIdOrFail')->zeroOrMoreTimes()->andReturn(ServiceFactory::timeSlot());

    $handler = new AddServiceImageHandler($services, new RecordingEventDispatcher);
    $handler->handle(new AddServiceImageCommand(ServiceId::generate()->toString(), '../evil.jpg'));
})->throws(InvalidArgumentException::class);
