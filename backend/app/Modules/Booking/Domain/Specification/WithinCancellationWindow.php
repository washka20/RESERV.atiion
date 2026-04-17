<?php

declare(strict_types=1);

namespace App\Modules\Booking\Domain\Specification;

use App\Modules\Booking\Domain\Entity\Booking;
use App\Modules\Booking\Domain\ValueObject\BookingType;
use App\Shared\Domain\Specification\Specification;
use DateTimeImmutable;

/**
 * Спецификация: отменить можно минимум за $minHoursBefore до начала.
 */
final class WithinCancellationWindow extends Specification
{
    public function __construct(
        private readonly int $minHoursBefore,
    ) {}

    public function isSatisfiedBy(mixed $candidate): bool
    {
        if (! $candidate instanceof Booking) {
            $this->recordFailure('candidate is not a Booking');

            return false;
        }

        $startsAt = $candidate->type === BookingType::TIME_SLOT
            ? $candidate->timeRange?->startAt
            : $candidate->dateRange?->checkIn;

        if ($startsAt === null) {
            $this->recordFailure('booking has no start date');

            return false;
        }

        $now = new DateTimeImmutable();
        $hoursUntilStart = ($startsAt->getTimestamp() - $now->getTimestamp()) / 3600;
        if ($hoursUntilStart < $this->minHoursBefore) {
            $this->recordFailure(sprintf(
                'too late to cancel: must be at least %d hours before start',
                $this->minHoursBefore,
            ));

            return false;
        }

        return true;
    }
}
