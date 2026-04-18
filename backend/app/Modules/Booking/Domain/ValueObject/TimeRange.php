<?php

declare(strict_types=1);

namespace App\Modules\Booking\Domain\ValueObject;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Временной диапазон для бронирования слота (startAt..endAt).
 *
 * Инвариант: endAt строго больше startAt. Поддерживает расчёт
 * длительности, проверку пересечений и принадлежности момента.
 */
final readonly class TimeRange
{
    public function __construct(
        public DateTimeImmutable $startAt,
        public DateTimeImmutable $endAt,
    ) {
        if ($endAt <= $startAt) {
            throw new InvalidArgumentException('TimeRange: endAt must be strictly greater than startAt');
        }
    }

    /**
     * Длительность диапазона в минутах.
     */
    public function durationInMinutes(): int
    {
        return (int) (($this->endAt->getTimestamp() - $this->startAt->getTimestamp()) / 60);
    }

    /**
     * Проверяет пересечение с другим диапазоном (полуоткрытые интервалы [start, end)).
     */
    public function overlaps(self $other): bool
    {
        return $this->startAt < $other->endAt && $other->startAt < $this->endAt;
    }

    /**
     * Проверяет, попадает ли момент в диапазон [startAt, endAt).
     */
    public function contains(DateTimeImmutable $moment): bool
    {
        return $moment >= $this->startAt && $moment < $this->endAt;
    }
}
