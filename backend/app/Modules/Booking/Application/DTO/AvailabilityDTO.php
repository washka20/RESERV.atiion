<?php

declare(strict_types=1);

namespace App\Modules\Booking\Application\DTO;

/**
 * Базовый DTO результата проверки доступности.
 *
 * Полиморфный: подклассы добавляют type-specific поля (slots для TIME_SLOT,
 * total/booked/available/requested для QUANTITY).
 */
abstract readonly class AvailabilityDTO
{
    public function __construct(
        public string $type,
        public bool $available,
    ) {}
}
