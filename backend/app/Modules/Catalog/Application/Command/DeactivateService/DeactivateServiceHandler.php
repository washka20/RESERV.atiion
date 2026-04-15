<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Command\DeactivateService;

use App\Modules\Catalog\Domain\Exception\ServiceNotFoundException;
use App\Modules\Catalog\Domain\Repository\ServiceRepositoryInterface;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use App\Shared\Application\Event\DomainEventDispatcherInterface;

final readonly class DeactivateServiceHandler
{
    public function __construct(
        private ServiceRepositoryInterface $services,
        private DomainEventDispatcherInterface $dispatcher,
    ) {}

    /**
     * Деактивирует услугу. Идемпотентно.
     *
     * @throws ServiceNotFoundException если услуги нет
     */
    public function handle(DeactivateServiceCommand $command): void
    {
        $service = $this->services->findByIdOrFail(new ServiceId($command->serviceId));
        $service->deactivate();
        $this->services->save($service);
        $this->dispatcher->dispatchAll($service->pullDomainEvents());
    }
}
