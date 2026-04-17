<?php

declare(strict_types=1);

namespace App\Modules\Booking\Domain\ValueObject;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Диапазон дат для бронирования типа QUANTITY (checkIn..checkOut).
 *
 * Семантика «по дням» — checkIn/checkOut без учёта времени.
 * Инвариант: checkOut строго позже checkIn.
 */
final readonly class DateRange
{
    public function __construct(
        public DateTimeImmutable $checkIn,
        public DateTimeImmutable $checkOut,
    ) {
        if ($checkOut <= $checkIn) {
            throw new InvalidArgumentException('DateRange: checkOut must be after checkIn');
        }
    }

    /**
     * Фабрика из строковых дат (Y-m-d).
     */
    public static function fromStrings(string $checkIn, string $checkOut): self
    {
        return new self(new DateTimeImmutable($checkIn), new DateTimeImmutable($checkOut));
    }

    /**
     * Количество ночей (полных суток между checkIn и checkOut).
     */
    public function nights(): int
    {
        return (int) $this->checkIn->diff($this->checkOut)->days;
    }

    /**
     * Проверяет пересечение с другим диапазоном дат.
     */
    public function overlaps(self $other): bool
    {
        return $this->checkIn < $other->checkOut && $other->checkIn < $this->checkOut;
    }
}
