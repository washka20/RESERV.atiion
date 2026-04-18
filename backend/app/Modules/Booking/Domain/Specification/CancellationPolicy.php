<?php

declare(strict_types=1);

namespace App\Modules\Booking\Domain\Specification;

use App\Modules\Booking\Domain\Entity\Booking;
use App\Shared\Domain\Specification\Specification;

/**
 * Композитная политика отмены бронирования.
 *
 * Комбинирует BookingNotAlreadyCompleted AND WithinCancellationWindow.
 * failureReason() возвращает причину от первой упавшей подспецификации.
 */
class CancellationPolicy extends Specification
{
    public function __construct(
        private readonly BookingNotAlreadyCompleted $notCompleted,
        private readonly WithinCancellationWindow $withinWindow,
    ) {}

    public function isSatisfiedBy(mixed $candidate): bool
    {
        if (! $candidate instanceof Booking) {
            $this->recordFailure('candidate is not a Booking');

            return false;
        }

        if (! $this->notCompleted->isSatisfiedBy($candidate)) {
            $this->recordFailure($this->notCompleted->failureReason() ?? 'booking is completed');

            return false;
        }

        if (! $this->withinWindow->isSatisfiedBy($candidate)) {
            $this->recordFailure($this->withinWindow->failureReason() ?? 'outside cancellation window');

            return false;
        }

        return true;
    }
}
