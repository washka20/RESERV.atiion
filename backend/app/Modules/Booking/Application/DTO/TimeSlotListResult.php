<?php

declare(strict_types=1);

namespace App\Modules\Booking\Application\DTO;

/**
 * Paginated-обёртка списка временных слотов.
 */
final readonly class TimeSlotListResult
{
    /**
     * @param  list<TimeSlotDTO>  $data
     */
    public function __construct(
        public array $data,
        public int $total,
        public int $page,
        public int $perPage,
        public int $lastPage,
    ) {}
}
