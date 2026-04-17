<?php

declare(strict_types=1);

namespace App\Modules\Booking\Domain\Event;

use App\Modules\Booking\Domain\ValueObject\SlotId;
use App\Shared\Domain\DomainEvent;
use DateTimeImmutable;

/**
 * Временной слот освобождён (например, при отмене бронирования).
 */
final readonly class TimeSlotReleased implements DomainEvent
{
    public function __construct(
        private SlotId $slotId,
        private DateTimeImmutable $occurredAt,
    ) {}

    public function slotId(): SlotId
    {
        return $this->slotId;
    }

    public function aggregateId(): string
    {
        return $this->slotId->toString();
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function eventName(): string
    {
        return 'booking.time_slot.released';
    }

    public function payload(): array
    {
        return [
            'slot_id' => $this->slotId->toString(),
            'occurred_at' => $this->occurredAt->format(DATE_ATOM),
        ];
    }
}
