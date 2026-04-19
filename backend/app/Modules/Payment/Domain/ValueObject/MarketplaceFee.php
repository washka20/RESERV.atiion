<?php

declare(strict_types=1);

namespace App\Modules\Payment\Domain\ValueObject;

use App\Modules\Catalog\Domain\ValueObject\Money;

/**
 * Результат расчёта marketplace fee.
 *
 * Содержит gross (исходная сумма), fee (комиссия площадки) и net (чистая выплата провайдеру).
 * Используется banker's rounding (PHP_ROUND_HALF_EVEN) для минимизации системной ошибки округления.
 */
final readonly class MarketplaceFee
{
    private function __construct(
        private Money $gross,
        private Money $fee,
        private Money $net,
    ) {}

    /**
     * Рассчитывает fee и net от gross по заданному проценту с banker's rounding.
     */
    public static function calculate(Money $gross, Percentage $percentage): self
    {
        $feeCents = (int) round(
            $gross->amount() * $percentage->value() / 100,
            0,
            PHP_ROUND_HALF_EVEN,
        );

        $fee = Money::fromCents($feeCents, $gross->currency());
        $net = $gross->subtract($fee);

        return new self($gross, $fee, $net);
    }

    public function gross(): Money
    {
        return $this->gross;
    }

    public function fee(): Money
    {
        return $this->fee;
    }

    public function net(): Money
    {
        return $this->net;
    }
}
