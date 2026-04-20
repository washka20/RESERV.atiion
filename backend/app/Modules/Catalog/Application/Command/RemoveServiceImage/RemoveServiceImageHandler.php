<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Command\RemoveServiceImage;

use App\Modules\Catalog\Domain\Exception\ServiceNotFoundException;
use App\Modules\Catalog\Domain\Repository\ServiceRepositoryInterface;
use App\Modules\Catalog\Domain\ValueObject\ImagePath;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use App\Shared\Application\Event\DomainEventDispatcherInterface;
use App\Shared\Application\Media\MediaStorageInterface;

final readonly class RemoveServiceImageHandler
{
    public function __construct(
        private ServiceRepositoryInterface $services,
        private MediaStorageInterface $storage,
        private DomainEventDispatcherInterface $dispatcher,
    ) {}

    /**
     * Удаляет изображение из услуги + файл из S3/MinIO.
     *
     * Идемпотентно: если файла нет в хранилище, delete() молчит.
     *
     * @throws ServiceNotFoundException если услуги нет
     */
    public function handle(RemoveServiceImageCommand $command): void
    {
        $service = $this->services->findByIdOrFail(new ServiceId($command->serviceId));
        $service->removeImage(ImagePath::fromString($command->imagePath));
        $this->services->save($service);
        $this->dispatcher->dispatchAll($service->pullDomainEvents());

        $this->storage->delete($command->imagePath);
    }
}
