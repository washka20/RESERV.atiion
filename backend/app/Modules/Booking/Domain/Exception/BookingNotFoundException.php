<?php

declare(strict_types=1);

namespace App\Modules\Booking\Domain\Exception;

use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Shared\Domain\Exception\DomainException;

/**
 * Бросается, когда бронирование не найдено по идентификатору.
 */
final class BookingNotFoundException extends DomainException
{
    public static function byId(BookingId $id): self
    {
        return new self(sprintf('Booking with id "%s" not found', $id->toString()));
    }

    public function errorCode(): string
    {
        return 'BOOKING_NOT_FOUND';
    }
}
