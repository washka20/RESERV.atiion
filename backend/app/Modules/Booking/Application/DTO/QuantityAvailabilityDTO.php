<?php

declare(strict_types=1);

namespace App\Modules\Booking\Application\DTO;

use App\Modules\Booking\Domain\Service\AvailabilityResult;

/**
 * DTO доступности для QUANTITY услуг.
 *
 * Отдаёт total/booked/available/requested — клиент решает, достаточно ли
 * available под requested (available уже рассчитан стратегией).
 */
final readonly class QuantityAvailabilityDTO extends AvailabilityDTO
{
    public function __construct(
        bool $available,
        public int $total,
        public int $booked,
        public int $availableQuantity,
        public int $requested,
    ) {
        parent::__construct('quantity', $available);
    }

    /**
     * Собирает DTO из AvailabilityResult стратегии QUANTITY.
     */
    public static function fromResult(AvailabilityResult $result): self
    {
        return new self(
            available: $result->available,
            total: (int) ($result->details['total'] ?? 0),
            booked: (int) ($result->details['booked'] ?? 0),
            availableQuantity: (int) ($result->details['available'] ?? 0),
            requested: (int) ($result->details['requested'] ?? 0),
        );
    }
}
