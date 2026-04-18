<?php

declare(strict_types=1);

namespace App\Modules\Booking\Domain\Exception;

use App\Modules\Booking\Domain\ValueObject\SlotId;
use App\Shared\Domain\Exception\DomainException;

/**
 * Бросается при попытке забронировать недоступный слот (логическая проверка до persistence).
 */
final class SlotUnavailableException extends DomainException
{
    public static function forSlotId(SlotId $slotId): self
    {
        return new self(sprintf('Slot %s is not available', $slotId->toString()));
    }

    public function errorCode(): string
    {
        return 'BOOKING_SLOT_UNAVAILABLE';
    }
}
