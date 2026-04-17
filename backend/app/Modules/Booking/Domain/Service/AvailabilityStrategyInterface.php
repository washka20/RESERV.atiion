<?php

declare(strict_types=1);

namespace App\Modules\Booking\Domain\Service;

use App\Modules\Catalog\Domain\ValueObject\ServiceId;

/**
 * Контракт стратегии проверки доступности (Strategy pattern).
 *
 * Реализации: TimeSlotAvailabilityStrategy, QuantityAvailabilityStrategy.
 */
interface AvailabilityStrategyInterface
{
    /**
     * @param  array<string, mixed>  $params  параметры в зависимости от стратегии:
     *                                        - TIME_SLOT: ['date' => 'Y-m-d']
     *                                        - QUANTITY: ['check_in' => 'Y-m-d', 'check_out' => 'Y-m-d', 'requested' => int]
     */
    public function check(ServiceId $serviceId, array $params): AvailabilityResult;
}
