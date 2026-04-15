<?php

declare(strict_types=1);

use App\Modules\Catalog\Application\Command\RemoveServiceImage\RemoveServiceImageCommand;
use App\Modules\Catalog\Application\Command\RemoveServiceImage\RemoveServiceImageHandler;
use App\Modules\Catalog\Domain\Repository\ServiceRepositoryInterface;
use App\Modules\Catalog\Domain\ValueObject\ImagePath;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use Tests\Unit\Modules\Catalog\Application\Support\RecordingEventDispatcher;
use Tests\Unit\Modules\Catalog\Application\Support\ServiceFactory;

it('removes image from service', function (): void {
    $services = Mockery::mock(ServiceRepositoryInterface::class);
    $id = ServiceId::generate();
    $service = ServiceFactory::timeSlot($id);
    $service->addImage(ImagePath::fromString('a.jpg'));
    $service->addImage(ImagePath::fromString('b.jpg'));
    $services->shouldReceive('findByIdOrFail')->once()->andReturn($service);
    $services->shouldReceive('save')->once();

    $handler = new RemoveServiceImageHandler($services, new RecordingEventDispatcher);
    $handler->handle(new RemoveServiceImageCommand($id->toString(), 'a.jpg'));

    expect($service->images())->toHaveCount(1);
    expect($service->images()[0]->value())->toBe('b.jpg');
});

it('is idempotent when image not present', function (): void {
    $services = Mockery::mock(ServiceRepositoryInterface::class);
    $id = ServiceId::generate();
    $service = ServiceFactory::timeSlot($id);
    $services->shouldReceive('findByIdOrFail')->once()->andReturn($service);
    $services->shouldReceive('save')->once();

    $handler = new RemoveServiceImageHandler($services, new RecordingEventDispatcher);
    $handler->handle(new RemoveServiceImageCommand($id->toString(), 'ghost.jpg'));

    expect($service->images())->toBeEmpty();
});
