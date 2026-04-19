<?php

declare(strict_types=1);

namespace App\Modules\Booking\Domain\Event;

use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Modules\Booking\Domain\ValueObject\SlotId;
use App\Shared\Domain\DomainEvent;
use DateTimeImmutable;

/**
 * Временной слот зарезервирован под конкретное бронирование.
 */
final readonly class TimeSlotReserved implements DomainEvent
{
    public function __construct(
        private SlotId $slotId,
        private BookingId $bookingId,
        private DateTimeImmutable $occurredAt,
    ) {}

    public function slotId(): SlotId
    {
        return $this->slotId;
    }

    public function bookingId(): BookingId
    {
        return $this->bookingId;
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
        return 'booking.time_slot.reserved';
    }

    public function payload(): array
    {
        return [
            'slot_id' => $this->slotId->toString(),
            'booking_id' => $this->bookingId->toString(),
            'occurred_at' => $this->occurredAt->format(DATE_ATOM),
        ];
    }

    public static function fromPayload(array $payload): self
    {
        return new self(
            new SlotId((string) $payload['slot_id']),
            new BookingId((string) $payload['booking_id']),
            new DateTimeImmutable((string) $payload['occurred_at']),
        );
    }
}
