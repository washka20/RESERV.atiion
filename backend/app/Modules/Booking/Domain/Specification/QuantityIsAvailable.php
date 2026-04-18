<?php

declare(strict_types=1);

namespace App\Modules\Booking\Domain\Specification;

use App\Shared\Domain\Specification\Specification;

/**
 * Спецификация: запрошенное количество доступно в диапазоне.
 *
 * Принимает контекст-массив: ['booked' => int, 'requested' => int, 'total' => int].
 * Удовлетворена если booked + requested <= total.
 */
final class QuantityIsAvailable extends Specification
{
    public function isSatisfiedBy(mixed $candidate): bool
    {
        if (! is_array($candidate)) {
            $this->recordFailure('candidate must be an array');

            return false;
        }
        $booked = (int) ($candidate['booked'] ?? -1);
        $requested = (int) ($candidate['requested'] ?? -1);
        $total = (int) ($candidate['total'] ?? -1);

        if ($booked < 0 || $requested <= 0 || $total <= 0) {
            $this->recordFailure('invalid quantity context');

            return false;
        }

        if ($booked + $requested > $total) {
            $this->recordFailure(sprintf(
                'insufficient quantity: booked %d + requested %d > total %d',
                $booked,
                $requested,
                $total,
            ));

            return false;
        }

        return true;
    }
}
