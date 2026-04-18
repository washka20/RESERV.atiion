<?php

declare(strict_types=1);

namespace App\Modules\Booking\Application\Query\ListAllBookings;

/**
 * Admin-запрос всех бронирований с фильтрами. Используется в админке/внутренних отчётах.
 *
 * $dateFrom / $dateTo фильтруют по created_at (включительно).
 */
final readonly class ListAllBookingsQuery
{
    public function __construct(
        public ?string $status = null,
        public ?string $serviceId = null,
        public ?string $userId = null,
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
        public int $page = 1,
        public int $perPage = 20,
    ) {}
}
