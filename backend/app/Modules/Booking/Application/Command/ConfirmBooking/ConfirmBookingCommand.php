<?php

declare(strict_types=1);

namespace App\Modules\Booking\Application\Command\ConfirmBooking;

/**
 * Команда подтверждения бронирования (PENDING -> CONFIRMED).
 *
 * Обычно вызывается из admin-панели или после успешной оплаты.
 */
final readonly class ConfirmBookingCommand
{
    public function __construct(
        public string $bookingId,
    ) {}
}
