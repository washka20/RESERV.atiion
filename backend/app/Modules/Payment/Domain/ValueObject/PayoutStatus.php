<?php

declare(strict_types=1);

namespace App\Modules\Payment\Domain\ValueObject;

/**
 * Статус выплаты провайдеру (payout).
 *
 * Переходы:
 * - PENDING → PROCESSING
 * - PROCESSING → PAID, PROCESSING → FAILED
 * - PAID, FAILED — терминальные состояния
 *
 * PENDING → PAID напрямую запрещён: выплата обязана пройти через PROCESSING.
 */
enum PayoutStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case PAID = 'paid';
    case FAILED = 'failed';

    /**
     * Проверяет допустимость перехода в указанный статус.
     */
    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::PENDING => $target === self::PROCESSING,
            self::PROCESSING => $target === self::PAID || $target === self::FAILED,
            self::PAID, self::FAILED => false,
        };
    }

    /**
     * Терминальные статусы (PAID, FAILED) не допускают дальнейших переходов.
     */
    public function isTerminal(): bool
    {
        return $this === self::PAID || $this === self::FAILED;
    }
}
