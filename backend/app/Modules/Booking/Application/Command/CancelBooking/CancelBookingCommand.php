<?php

declare(strict_types=1);

namespace App\Modules\Booking\Application\Command\CancelBooking;

/**
 * Команда отмены бронирования.
 *
 * Авторизация: обычный пользователь может отменить только своё бронирование.
 * Администратор ($isAdmin=true) может отменить любое.
 */
final readonly class CancelBookingCommand
{
    public function __construct(
        public string $bookingId,
        public string $actorUserId,
        public bool $isAdmin = false,
    ) {}
}
