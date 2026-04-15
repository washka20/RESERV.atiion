<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\ValueObject;

use InvalidArgumentException;

/**
 * Длительность услуги в минутах. Всегда положительная.
 */
final readonly class Duration
{
    private function __construct(private int $minutes)
    {
        if ($minutes <= 0) {
            throw new InvalidArgumentException('Duration must be positive');
        }
    }

    public static function ofMinutes(int $minutes): self
    {
        return new self($minutes);
    }

    public function minutes(): int
    {
        return $this->minutes;
    }

    public function toHours(): float
    {
        return $this->minutes / 60;
    }

    public function add(self $other): self
    {
        return new self($this->minutes + $other->minutes);
    }

    public function equals(self $other): bool
    {
        return $this->minutes === $other->minutes;
    }
}
