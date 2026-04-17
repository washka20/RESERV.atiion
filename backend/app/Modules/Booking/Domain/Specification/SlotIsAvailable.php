<?php

declare(strict_types=1);

namespace App\Modules\Booking\Domain\Specification;

use App\Modules\Booking\Domain\Entity\TimeSlot;
use App\Shared\Domain\Specification\Specification;

/**
 * Спецификация: TimeSlot свободен для бронирования.
 */
final class SlotIsAvailable extends Specification
{
    public function isSatisfiedBy(mixed $candidate): bool
    {
        if (! $candidate instanceof TimeSlot) {
            $this->recordFailure('candidate is not a TimeSlot');

            return false;
        }
        if ($candidate->isBooked()) {
            $this->recordFailure('slot is already booked');

            return false;
        }

        return true;
    }
}
