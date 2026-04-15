<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Command\UpdateService;

use App\Modules\Catalog\Domain\Exception\ServiceNotFoundException;
use App\Modules\Catalog\Domain\Repository\ServiceRepositoryInterface;
use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use App\Shared\Application\Event\DomainEventDispatcherInterface;

final readonly class UpdateServiceHandler
{
    public function __construct(
        private ServiceRepositoryInterface $services,
        private DomainEventDispatcherInterface $dispatcher,
    ) {}

    /**
     * Обновляет name/description/price услуги.
     *
     * @throws ServiceNotFoundException если услуги нет
     */
    public function handle(UpdateServiceCommand $command): void
    {
        $service = $this->services->findByIdOrFail(new ServiceId($command->serviceId));
        $service->updateDetails(
            $command->name,
            $command->description,
            Money::fromCents($command->priceAmount, $command->priceCurrency),
        );
        $this->services->save($service);
        $this->dispatcher->dispatchAll($service->pullDomainEvents());
    }
}
