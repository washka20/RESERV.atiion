<?php

declare(strict_types=1);

namespace App\Modules\Booking\Domain\Specification;

use App\Modules\Booking\Domain\Entity\Booking;
use App\Shared\Domain\Specification\Specification;

/**
 * Композитная политика создания бронирования.
 *
 * Комбинирует WithinBookingWindow AND UserNotExceedsLimit.
 * UserNotExceedsLimit требует контекст с userActiveBookings — подаётся через isSatisfiedByWithContext.
 */
final class BookingPolicy extends Specification
{
    public function __construct(
        private readonly WithinBookingWindow $withinWindow,
        private readonly UserNotExceedsLimit $userLimit,
    ) {}

    /**
     * Проверка политики. Принимает Booking; для UserNotExceedsLimit использует default 0/limit из контекста.
     * Для полноценной проверки лимитов использовать isSatisfiedByWithContext().
     */
    public function isSatisfiedBy(mixed $candidate): bool
    {
        if (! $candidate instanceof Booking) {
            $this->recordFailure('candidate is not a Booking');

            return false;
        }

        if (! $this->withinWindow->isSatisfiedBy($candidate)) {
            $this->recordFailure($this->withinWindow->failureReason() ?? 'outside booking window');

            return false;
        }

        return true;
    }

    /**
     * Проверяет окно бронирования + лимит активных бронирований пользователя.
     */
    public function isSatisfiedByWithContext(Booking $booking, int $userActiveBookings, int $limit): bool
    {
        if (! $this->isSatisfiedBy($booking)) {
            return false;
        }

        if (! $this->userLimit->isSatisfiedBy([
            'userActiveBookings' => $userActiveBookings,
            'limit' => $limit,
        ])) {
            $this->recordFailure($this->userLimit->failureReason() ?? 'user limit exceeded');

            return false;
        }

        return true;
    }
}
