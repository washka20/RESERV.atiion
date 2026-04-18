<?php

declare(strict_types=1);

namespace App\Modules\Booking\Domain\Specification;

use App\Modules\Booking\Domain\Entity\Booking;
use App\Modules\Booking\Domain\ValueObject\BookingStatus;
use App\Shared\Domain\Specification\Specification;

/**
 * Спецификация: бронирование не в терминальном статусе COMPLETED.
 */
final class BookingNotAlreadyCompleted extends Specification
{
    public function isSatisfiedBy(mixed $candidate): bool
    {
        if (! $candidate instanceof Booking) {
            $this->recordFailure('candidate is not a Booking');

            return false;
        }

        if ($candidate->status() === BookingStatus::COMPLETED) {
            $this->recordFailure('booking is already completed');

            return false;
        }

        return true;
    }
}
