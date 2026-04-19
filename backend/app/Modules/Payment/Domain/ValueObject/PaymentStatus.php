<?php

declare(strict_types=1);

namespace App\Modules\Payment\Domain\ValueObject;

/**
 * Статус платежа.
 *
 * Переходы:
 * - PENDING → PAID, PENDING → FAILED
 * - PAID → REFUNDED
 * - REFUNDED, FAILED — терминальные состояния
 */
enum PaymentStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case REFUNDED = 'refunded';
    case FAILED = 'failed';

    /**
     * Проверяет допустимость перехода в указанный статус.
     */
    public function canTransitionTo(self $to): bool
    {
        return match ($this) {
            self::PENDING => $to === self::PAID || $to === self::FAILED,
            self::PAID => $to === self::REFUNDED,
            self::REFUNDED, self::FAILED => false,
        };
    }

    /**
     * Терминальные статусы (REFUNDED, FAILED) не допускают дальнейших переходов.
     */
    public function isTerminal(): bool
    {
        return $this === self::REFUNDED || $this === self::FAILED;
    }
}
