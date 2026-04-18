<?php

declare(strict_types=1);

namespace App\Modules\Booking\Domain\Specification;

use App\Shared\Domain\Specification\Specification;

/**
 * Спецификация: у пользователя не больше $limit активных бронирований.
 *
 * Принимает контекст: ['userActiveBookings' => int, 'limit' => int].
 */
final class UserNotExceedsLimit extends Specification
{
    public function isSatisfiedBy(mixed $candidate): bool
    {
        if (! is_array($candidate)) {
            $this->recordFailure('candidate must be an array');

            return false;
        }
        $active = (int) ($candidate['userActiveBookings'] ?? -1);
        $limit = (int) ($candidate['limit'] ?? -1);

        if ($active < 0 || $limit <= 0) {
            $this->recordFailure('invalid limit context');

            return false;
        }

        if ($active >= $limit) {
            $this->recordFailure(sprintf(
                'user has reached active bookings limit (%d)',
                $limit,
            ));

            return false;
        }

        return true;
    }
}
