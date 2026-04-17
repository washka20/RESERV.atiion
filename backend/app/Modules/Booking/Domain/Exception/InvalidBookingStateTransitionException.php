<?php

declare(strict_types=1);

namespace App\Modules\Booking\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

/**
 * Бросается при недопустимом переходе статуса бронирования.
 * Пример: confirm из CANCELLED, complete из PENDING.
 */
final class InvalidBookingStateTransitionException extends DomainException
{
    public static function withMessage(string $message): self
    {
        return new self($message);
    }

    public function errorCode(): string
    {
        return 'BOOKING_INVALID_STATE_TRANSITION';
    }
}
