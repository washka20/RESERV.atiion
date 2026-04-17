<?php

declare(strict_types=1);

namespace App\Modules\Booking\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

/**
 * Бросается, когда тип бронирования не соответствует типу услуги или операции.
 * Пример: попытка создать QUANTITY booking для TIME_SLOT услуги.
 */
final class InvalidBookingTypeException extends DomainException
{
    public static function mismatch(string $serviceType, string $bookingType): self
    {
        return new self(sprintf(
            'Booking type mismatch: service is %s, got %s',
            $serviceType,
            $bookingType,
        ));
    }

    public function errorCode(): string
    {
        return 'BOOKING_INVALID_TYPE';
    }
}
