<?php

declare(strict_types=1);

namespace App\Modules\Booking\Application\Query\ListUserBookings;

/**
 * Запрос списка бронирований конкретного пользователя с опциональным фильтром по статусу.
 */
final readonly class ListUserBookingsQuery
{
    public function __construct(
        public string $userId,
        public ?string $status = null,
        public int $page = 1,
        public int $perPage = 20,
    ) {}
}
