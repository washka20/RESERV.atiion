<?php

declare(strict_types=1);

namespace App\Modules\Booking\Application\Query\ListTimeSlots;

/**
 * Запрос списка временных слотов — для админки/каталога.
 *
 * $dateFrom / $dateTo фильтруют по start_at.
 */
final readonly class ListTimeSlotsQuery
{
    public function __construct(
        public ?string $serviceId = null,
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
        public ?bool $isBooked = null,
        public int $page = 1,
        public int $perPage = 20,
    ) {}
}
