<?php

declare(strict_types=1);

namespace App\Modules\Booking\Application\DTO;

/**
 * DTO временного слота для read-эндпоинтов (админка / каталог).
 */
final readonly class TimeSlotDTO
{
    public function __construct(
        public string $id,
        public string $serviceId,
        public string $startAt,
        public string $endAt,
        public bool $isBooked,
        public ?string $bookingId,
    ) {}
}
