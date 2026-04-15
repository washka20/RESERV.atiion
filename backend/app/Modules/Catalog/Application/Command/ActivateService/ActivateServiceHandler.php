<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Command\ActivateService;

use App\Modules\Catalog\Domain\Exception\ServiceNotFoundException;
use App\Modules\Catalog\Domain\Repository\ServiceRepositoryInterface;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use App\Shared\Application\Event\DomainEventDispatcherInterface;

final readonly class ActivateServiceHandler
{
    public function __construct(
        private ServiceRepositoryInterface $services,
        private DomainEventDispatcherInterface $dispatcher,
    ) {}

    /**
     * Активирует услугу. Идемпотентно.
     *
     * @throws ServiceNotFoundException если услуги нет
     */
    public function handle(ActivateServiceCommand $command): void
    {
        $service = $this->services->findByIdOrFail(new ServiceId($command->serviceId));
        $service->activate();
        $this->services->save($service);
        $this->dispatcher->dispatchAll($service->pullDomainEvents());
    }
}
