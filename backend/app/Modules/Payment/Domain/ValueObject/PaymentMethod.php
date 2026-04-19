<?php

declare(strict_types=1);

namespace App\Modules\Payment\Domain\ValueObject;

/**
 * Способ оплаты.
 */
enum PaymentMethod: string
{
    case CARD = 'card';
    case BANK_TRANSFER = 'bank_transfer';
    case SBP = 'sbp';
    case CASH = 'cash';

    /**
     * Отображаемая локализованная метка (ru).
     */
    public function label(): string
    {
        return match ($this) {
            self::CARD => 'Карта',
            self::BANK_TRANSFER => 'Банковский перевод',
            self::SBP => 'СБП',
            self::CASH => 'Наличные',
        };
    }
}
