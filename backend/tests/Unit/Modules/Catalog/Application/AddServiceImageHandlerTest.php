<?php

declare(strict_types=1);

use App\Modules\Catalog\Application\Command\AddServiceImage\AddServiceImageCommand;
use App\Modules\Catalog\Application\Command\AddServiceImage\AddServiceImageHandler;
use App\Modules\Catalog\Domain\Repository\ServiceRepositoryInterface;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use App\Shared\Application\Media\MediaStorageInterface;
use App\Shared\Application\Media\MediaValidationException;
use App\Shared\Application\Media\UploadedFileInterface;
use Tests\Unit\Modules\Catalog\Application\Support\RecordingEventDispatcher;
use Tests\Unit\Modules\Catalog\Application\Support\ServiceFactory;

function fakeUploadedFile(): UploadedFileInterface
{
    $file = Mockery::mock(UploadedFileInterface::class);
    $file->shouldReceive('clientMimeType')->andReturn('image/jpeg');
    $file->shouldReceive('clientExtension')->andReturn('jpg');
    $file->shouldReceive('sizeBytes')->andReturn(12345);
    $file->shouldReceive('getRealPath')->andReturn('/tmp/fake');
    $file->shouldReceive('getClientOriginalName')->andReturn('photo.jpg');

    return $file;
}

it('хранит файл через MediaStorage и добавляет path в service', function (): void {
    $services = Mockery::mock(ServiceRepositoryInterface::class);
    $storage = Mockery::mock(MediaStorageInterface::class);
    $id = ServiceId::generate();
    $service = ServiceFactory::timeSlot($id);
    $services->shouldReceive('findByIdOrFail')->once()->andReturn($service);
    $services->shouldReceive('save')->once();

    $storage->shouldReceive('store')
        ->once()
        ->with(Mockery::type(UploadedFileInterface::class), "services/{$id}")
        ->andReturn("services/{$id}/abc-123.jpg");

    $handler = new AddServiceImageHandler($services, $storage, new RecordingEventDispatcher);
    $handler->handle(new AddServiceImageCommand($id->toString(), fakeUploadedFile()));

    expect($service->images())->toHaveCount(1);
    expect($service->images()[0]->value())->toBe("services/{$id}/abc-123.jpg");
});

it('пробрасывает MediaValidationException из storage', function (): void {
    $services = Mockery::mock(ServiceRepositoryInterface::class);
    $storage = Mockery::mock(MediaStorageInterface::class);
    $services->shouldReceive('findByIdOrFail')->once()->andReturn(ServiceFactory::timeSlot());

    $storage->shouldReceive('store')->once()->andThrow(MediaValidationException::mime('text/plain'));

    $handler = new AddServiceImageHandler($services, $storage, new RecordingEventDispatcher);
    $handler->handle(new AddServiceImageCommand(ServiceId::generate()->toString(), fakeUploadedFile()));
})->throws(MediaValidationException::class);
