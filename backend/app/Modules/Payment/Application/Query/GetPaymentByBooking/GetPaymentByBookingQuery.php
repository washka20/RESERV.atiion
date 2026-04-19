<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\Query\GetPaymentByBooking;

use App\Modules\Booking\Domain\ValueObject\BookingId;

/**
 * Query: получить платёж по идентификатору бронирования (read-side, без Eloquent).
 */
final readonly class GetPaymentByBookingQuery
{
    public function __construct(
        public BookingId $bookingId,
    ) {}
}
