<?php

declare(strict_types=1);

namespace App\Modules\Payment\Domain\ValueObject;

use InvalidArgumentException;

/**
 * Immutable percentage value (0..100, integer).
 *
 * Используется для расчёта marketplace fee и других доменных процентов.
 */
final readonly class Percentage
{
    private function __construct(private int $value)
    {
        if ($value < 0 || $value > 100) {
            throw new InvalidArgumentException("Percentage must be 0..100, got {$value}");
        }
    }

    public static function fromInt(int $v): self
    {
        return new self($v);
    }

    public function value(): int
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
