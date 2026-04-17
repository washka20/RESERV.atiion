<?php

declare(strict_types=1);

namespace App\Modules\Booking\Application\DTO;

/**
 * Облегчённый DTO элемента списка бронирований — для list-эндпоинтов.
 *
 * В отличие от BookingDTO не содержит notes / updatedAt / endAt. Заполняется
 * из сырых строк `DB::table('bookings')` без загрузки aggregate (read-side).
 */
final readonly class BookingListItemDTO
{
    public function __construct(
        public string $id,
        public string $serviceId,
        public string $type,
        public string $status,
        public ?string $slotId,
        public ?string $startAt,
        public ?string $checkIn,
        public ?string $checkOut,
        public ?int $quantity,
        public int $totalPriceAmount,
        public string $totalPriceCurrency,
        public string $createdAt,
    ) {}
}
