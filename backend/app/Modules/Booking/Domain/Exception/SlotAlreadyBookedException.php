<?php

declare(strict_types=1);

namespace App\Modules\Booking\Domain\Exception;

use App\Modules\Booking\Domain\ValueObject\SlotId;
use App\Shared\Domain\Exception\DomainException;

/**
 * Бросается при попытке reserve уже занятого TimeSlot на уровне агрегата.
 * Отличается от SlotUnavailableException — эта защищает инвариант entity.
 */
final class SlotAlreadyBookedException extends DomainException
{
    public static function forSlotId(SlotId $slotId): self
    {
        return new self(sprintf('Slot %s is already booked', $slotId->toString()));
    }

    public function errorCode(): string
    {
        return 'BOOKING_SLOT_ALREADY_BOOKED';
    }
}
