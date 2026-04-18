<?php

declare(strict_types=1);

namespace App\Modules\Booking\Domain\ValueObject;

use InvalidArgumentException;

/**
 * Количество единиц для бронирования типа QUANTITY.
 *
 * Инвариант: значение строго положительное.
 */
final readonly class Quantity
{
    public function __construct(public int $value)
    {
        if ($value <= 0) {
            throw new InvalidArgumentException('Quantity must be positive');
        }
    }
}
