<?php

declare(strict_types=1);

namespace App\Modules\Booking\Application\Query\CheckAvailability;

use App\Modules\Booking\Application\DTO\AvailabilityDTO;
use App\Modules\Booking\Application\DTO\QuantityAvailabilityDTO;
use App\Modules\Booking\Application\DTO\TimeSlotAvailabilityDTO;
use App\Modules\Booking\Domain\Service\AvailabilityChecker;
use App\Modules\Catalog\Domain\Exception\ServiceNotFoundException;
use App\Modules\Catalog\Domain\Repository\ServiceRepositoryInterface;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use App\Modules\Catalog\Domain\ValueObject\ServiceType;

/**
 * Handler проверки доступности услуги — delegirуется на AvailabilityChecker и
 * оборачивает результат в type-specific DTO.
 */
final readonly class CheckAvailabilityHandler
{
    public function __construct(
        private ServiceRepositoryInterface $serviceRepo,
        private AvailabilityChecker $checker,
    ) {}

    /**
     * @throws ServiceNotFoundException если услуги нет
     */
    public function handle(CheckAvailabilityQuery $query): AvailabilityDTO
    {
        $serviceId = new ServiceId($query->serviceId);
        $service = $this->serviceRepo->findById($serviceId);
        if ($service === null) {
            throw ServiceNotFoundException::byId($serviceId);
        }

        $result = $this->checker->check($service->type(), $service->id(), $query->params);

        return match ($service->type()) {
            ServiceType::TIME_SLOT => TimeSlotAvailabilityDTO::fromResult($result),
            ServiceType::QUANTITY => QuantityAvailabilityDTO::fromResult($result),
        };
    }
}
