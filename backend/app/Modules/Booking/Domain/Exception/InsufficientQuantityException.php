<?php

declare(strict_types=1);

namespace App\Modules\Booking\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

/**
 * Бросается, когда запрошенное количество превышает доступное на диапазон дат.
 */
final class InsufficientQuantityException extends DomainException
{
    public static function withDetails(int $requested, int $available): self
    {
        return new self(sprintf(
            'Insufficient quantity: requested %d, available %d',
            $requested,
            $available,
        ));
    }

    public function errorCode(): string
    {
        return 'BOOKING_INSUFFICIENT_QUANTITY';
    }
}
