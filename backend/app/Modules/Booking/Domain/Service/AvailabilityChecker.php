<?php

declare(strict_types=1);

namespace App\Modules\Booking\Domain\Service;

use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use App\Modules\Catalog\Domain\ValueObject\ServiceType;

/**
 * Диспатчер стратегий доступности. Выбирает стратегию по ServiceType.
 */
final readonly class AvailabilityChecker
{
    public function __construct(
        private TimeSlotAvailabilityStrategy $timeSlotStrategy,
        private QuantityAvailabilityStrategy $quantityStrategy,
    ) {}

    /**
     * @param  array<string, mixed>  $params
     */
    public function check(ServiceType $type, ServiceId $serviceId, array $params): AvailabilityResult
    {
        return match ($type) {
            ServiceType::TIME_SLOT => $this->timeSlotStrategy->check($serviceId, $params),
            ServiceType::QUANTITY => $this->quantityStrategy->check($serviceId, $params),
        };
    }
}
