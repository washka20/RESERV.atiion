<?php

declare(strict_types=1);

namespace App\Modules\Booking\Application\Query\GetBooking;

/**
 * Запрос получения бронирования по id.
 *
 * $actorUserId — id пользователя, запрашивающего данные (для ownership-проверки).
 * $isAdmin     — обходит ownership-проверку (для admin-эндпоинтов).
 */
final readonly class GetBookingQuery
{
    public function __construct(
        public string $bookingId,
        public string $actorUserId,
        public bool $isAdmin = false,
    ) {}
}
