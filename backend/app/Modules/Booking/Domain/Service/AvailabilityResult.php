<?php

declare(strict_types=1);

namespace App\Modules\Booking\Domain\Service;

/**
 * Результат проверки доступности.
 *
 * $details — полиморфный массив, содержимое зависит от стратегии:
 *   - TimeSlot: ['slots' => TimeSlot[]]
 *   - Quantity: ['total' => int, 'booked' => int, 'available' => int, 'requested' => int]
 */
final readonly class AvailabilityResult
{
    /**
     * @param  array<string, mixed>  $details
     */
    public function __construct(
        public bool $available,
        public array $details,
    ) {}
}
