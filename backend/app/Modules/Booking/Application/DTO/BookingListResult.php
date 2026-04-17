<?php

declare(strict_types=1);

namespace App\Modules\Booking\Application\DTO;

/**
 * Paginated-обёртка списка бронирований (envelope-compatible поля page/per_page/total/last_page).
 */
final readonly class BookingListResult
{
    /**
     * @param  list<BookingListItemDTO>  $data
     */
    public function __construct(
        public array $data,
        public int $total,
        public int $page,
        public int $perPage,
        public int $lastPage,
    ) {}
}
