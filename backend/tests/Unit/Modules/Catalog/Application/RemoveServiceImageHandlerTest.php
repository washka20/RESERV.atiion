<?php

declare(strict_types=1);

use App\Modules\Catalog\Application\Command\RemoveServiceImage\RemoveServiceImageCommand;
use App\Modules\Catalog\Application\Command\RemoveServiceImage\RemoveServiceImageHandler;
use App\Modules\Catalog\Domain\Repository\ServiceRepositoryInterface;
use App\Modules\Catalog\Domain\ValueObject\ImagePath;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use App\Shared\Application\Media\MediaStorageInterface;
use Tests\Unit\Modules\Catalog\Application\Support\RecordingEventDispatcher;
use Tests\Unit\Modules\Catalog\Application\Support\ServiceFactory;

it('удаляет image из service + вызывает storage.delete', function (): void {
    $services = Mockery::mock(ServiceRepositoryInterface::class);
    $storage = Mockery::mock(MediaStorageInterface::class);
    $id = ServiceId::generate();
    $service = ServiceFactory::timeSlot($id);
    $service->addImage(ImagePath::fromString('a.jpg'));
    $service->addImage(ImagePath::fromString('b.jpg'));
    $services->shouldReceive('findByIdOrFail')->once()->andReturn($service);
    $services->shouldReceive('save')->once();
    $storage->shouldReceive('delete')->once()->with('a.jpg');

    $handler = new RemoveServiceImageHandler($services, $storage, new RecordingEventDispatcher);
    $handler->handle(new RemoveServiceImageCommand($id->toString(), 'a.jpg'));

    expect($service->images())->toHaveCount(1);
    expect($service->images()[0]->value())->toBe('b.jpg');
});

it('идемпотентен когда image не present в service (но storage.delete всё равно вызывается)', function (): void {
    $services = Mockery::mock(ServiceRepositoryInterface::class);
    $storage = Mockery::mock(MediaStorageInterface::class);
    $id = ServiceId::generate();
    $service = ServiceFactory::timeSlot($id);
    $services->shouldReceive('findByIdOrFail')->once()->andReturn($service);
    $services->shouldReceive('save')->once();
    $storage->shouldReceive('delete')->once()->with('ghost.jpg');

    $handler = new RemoveServiceImageHandler($services, $storage, new RecordingEventDispatcher);
    $handler->handle(new RemoveServiceImageCommand($id->toString(), 'ghost.jpg'));

    expect($service->images())->toBeEmpty();
});
