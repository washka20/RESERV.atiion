<?php

declare(strict_types=1);

namespace App\Modules\Booking\Application\Query\GetBookingById;

/**
 * Внутренний query получения бронирования по id без ownership-проверки.
 *
 * Используется listener'ами других BC (напр. Payment), когда нужно прочитать booking
 * по id из domain event без контекста actor user (system-level read).
 *
 * Для customer API / admin API используй GetBookingQuery с actorUserId.
 */
final readonly class GetBookingByIdQuery
{
    public function __construct(
        public string $bookingId,
    ) {}
}
