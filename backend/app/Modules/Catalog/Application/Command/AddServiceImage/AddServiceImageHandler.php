<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Command\AddServiceImage;

use App\Modules\Catalog\Domain\Exception\ServiceNotFoundException;
use App\Modules\Catalog\Domain\Repository\ServiceRepositoryInterface;
use App\Modules\Catalog\Domain\ValueObject\ImagePath;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use App\Shared\Application\Event\DomainEventDispatcherInterface;
use App\Shared\Application\Media\MediaStorageInterface;
use App\Shared\Application\Media\MediaValidationException;

final readonly class AddServiceImageHandler
{
    public function __construct(
        private ServiceRepositoryInterface $services,
        private MediaStorageInterface $storage,
        private DomainEventDispatcherInterface $dispatcher,
    ) {}

    /**
     * Загружает файл в S3/MinIO, сохраняет path в service_images.
     *
     * Путь: "services/{service_id}/{uuid}.{ext}". Валидация mime/size/ext
     * делается в MediaStorage::store (fail-fast до записи в БД).
     *
     * @throws ServiceNotFoundException если услуги нет
     * @throws MediaValidationException
     */
    public function handle(AddServiceImageCommand $command): void
    {
        $service = $this->services->findByIdOrFail(new ServiceId($command->serviceId));

        $path = $this->storage->store($command->file, "services/{$command->serviceId}");

        $service->addImage(ImagePath::fromString($path));
        $this->services->save($service);
        $this->dispatcher->dispatchAll($service->pullDomainEvents());
    }
}
