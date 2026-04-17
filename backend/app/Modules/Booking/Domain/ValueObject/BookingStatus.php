<?php

declare(strict_types=1);

namespace App\Modules\Booking\Domain\ValueObject;

/**
 * Статус бронирования.
 *
 * Активные: PENDING, CONFIRMED — бронирование в работе.
 * Финальные: CANCELLED, COMPLETED — бронирование завершено.
 */
enum BookingStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case CANCELLED = 'cancelled';
    case COMPLETED = 'completed';

    /**
     * Бронирование ещё в работе (можно отменить, подтвердить и т.д.).
     */
    public function isActive(): bool
    {
        return in_array($this, [self::PENDING, self::CONFIRMED], true);
    }

    /**
     * Бронирование завершено (терминальное состояние).
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::CANCELLED, self::COMPLETED], true);
    }
}
