<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Command\AddServiceImage;

use App\Modules\Catalog\Domain\Exception\ServiceNotFoundException;
use App\Modules\Catalog\Domain\Repository\ServiceRepositoryInterface;
use App\Modules\Catalog\Domain\ValueObject\ImagePath;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use App\Shared\Application\Event\DomainEventDispatcherInterface;

final readonly class AddServiceImageHandler
{
    public function __construct(
        private ServiceRepositoryInterface $services,
        private DomainEventDispatcherInterface $dispatcher,
    ) {}

    /**
     * Добавляет изображение к услуге. Идемпотентно по значению.
     *
     * @throws ServiceNotFoundException если услуги нет
     */
    public function handle(AddServiceImageCommand $command): void
    {
        $service = $this->services->findByIdOrFail(new ServiceId($command->serviceId));
        $service->addImage(ImagePath::fromString($command->imagePath));
        $this->services->save($service);
        $this->dispatcher->dispatchAll($service->pullDomainEvents());
    }
}
