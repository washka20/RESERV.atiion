<?php

declare(strict_types=1);

namespace App\Modules\Booking\Domain\Specification;

use App\Modules\Booking\Domain\Entity\Booking;
use App\Modules\Booking\Domain\ValueObject\BookingType;
use App\Shared\Domain\Specification\Specification;
use DateTimeImmutable;

/**
 * Спецификация: бронирование попадает в допустимое окно времени.
 *
 * - минимум за $minAdvanceMinutes до начала
 * - максимум за $maxAdvanceDays до начала
 *
 * Для TIME_SLOT сверяет с timeRange->startAt, для QUANTITY — с dateRange->checkIn.
 */
final class WithinBookingWindow extends Specification
{
    public function __construct(
        private readonly int $minAdvanceMinutes,
        private readonly int $maxAdvanceDays,
    ) {}

    public function isSatisfiedBy(mixed $candidate): bool
    {
        if (! $candidate instanceof Booking) {
            $this->recordFailure('candidate is not a Booking');

            return false;
        }

        $startsAt = $this->bookingStart($candidate);
        if ($startsAt === null) {
            $this->recordFailure('booking has no start date');

            return false;
        }

        $now = new DateTimeImmutable;
        $minDiff = ($startsAt->getTimestamp() - $now->getTimestamp()) / 60;
        if ($minDiff < $this->minAdvanceMinutes) {
            $this->recordFailure(sprintf(
                'booking is too close: must be at least %d minutes in advance',
                $this->minAdvanceMinutes,
            ));

            return false;
        }

        $maxDiffDays = ($startsAt->getTimestamp() - $now->getTimestamp()) / 86400;
        if ($maxDiffDays > $this->maxAdvanceDays) {
            $this->recordFailure(sprintf(
                'booking is too far: must be within %d days',
                $this->maxAdvanceDays,
            ));

            return false;
        }

        return true;
    }

    private function bookingStart(Booking $booking): ?DateTimeImmutable
    {
        if ($booking->type === BookingType::TIME_SLOT) {
            return $booking->timeRange?->startAt;
        }

        return $booking->dateRange?->checkIn;
    }
}
