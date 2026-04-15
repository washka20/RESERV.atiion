<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\ValueObject;

use InvalidArgumentException;

/**
 * Денежная сумма в минорных единицах (копейках/центах) с валютой.
 *
 * Поддерживает RUB, USD, EUR. Операции между разными валютами запрещены.
 * Храним как int (центы) чтобы избежать ошибок float-арифметики.
 */
final readonly class Money
{
    /**
     * @var array<int, string>
     */
    private const array ALLOWED_CURRENCIES = ['RUB', 'USD', 'EUR'];

    private function __construct(
        private int $amount,
        private string $currency,
    ) {
        if ($amount < 0) {
            throw new InvalidArgumentException('Money amount must be non-negative');
        }
        if (! in_array($currency, self::ALLOWED_CURRENCIES, true)) {
            throw new InvalidArgumentException("Unsupported currency: {$currency}");
        }
    }

    /**
     * Создаёт Money из минорных единиц (копеек/центов).
     */
    public static function fromCents(int $cents, string $currency = 'RUB'): self
    {
        return new self($cents, $currency);
    }

    /**
     * Создаёт Money из рублей (float → копейки, округление half-up).
     */
    public static function fromRubles(float $rubles): self
    {
        return new self((int) round($rubles * 100), 'RUB');
    }

    public function amount(): int
    {
        return $this->amount;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    /**
     * @throws InvalidArgumentException при разных валютах
     */
    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amount + $other->amount, $this->currency);
    }

    /**
     * @throws InvalidArgumentException при разных валютах или отрицательном результате
     */
    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amount - $other->amount, $this->currency);
    }

    public function multiply(int $factor): self
    {
        return new self($this->amount * $factor, $this->currency);
    }

    public function equals(self $other): bool
    {
        return $this->amount === $other->amount && $this->currency === $other->currency;
    }

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException(
                "Cannot operate on different currencies: {$this->currency} vs {$other->currency}"
            );
        }
    }
}
