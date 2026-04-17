<?php

declare(strict_types=1);

namespace App\Modules\Booking\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

/**
 * Бросается, когда политика отмены не удовлетворена (finalized, вне окна и т.д.).
 */
final class CancellationNotAllowedException extends DomainException
{
    public static function withReason(string $reason): self
    {
        return new self(sprintf('Cancellation not allowed: %s', $reason));
    }

    public function errorCode(): string
    {
        return 'BOOKING_CANCELLATION_NOT_ALLOWED';
    }
}
