<?php

declare(strict_types=1);

namespace App\Modules\Booking\Application\Command\CompleteBooking;

/**
 * Команда завершения бронирования (CONFIRMED -> COMPLETED).
 *
 * Обычно вызывается из admin-панели после фактического оказания услуги.
 */
final readonly class CompleteBookingCommand
{
    public function __construct(
        public string $bookingId,
    ) {}
}
