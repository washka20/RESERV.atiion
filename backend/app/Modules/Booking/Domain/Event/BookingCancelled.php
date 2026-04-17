<?php

declare(strict_types=1);

namespace App\Modules\Booking\Domain\Event;

use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Modules\Booking\Domain\ValueObject\BookingType;
use App\Modules\Booking\Domain\ValueObject\SlotId;
use App\Shared\Domain\DomainEvent;
use DateTimeImmutable;

/**
 * Бронирование отменено. Содержит slotId, если это TIME_SLOT booking — чтобы listener освободил слот.
 */
final readonly class BookingCancelled implements DomainEvent
{
    public function __construct(
        private BookingId $bookingId,
        private BookingType $type,
        private ?SlotId $slotId,
        private DateTimeImmutable $occurredAt,
    ) {}

    public function bookingId(): BookingId
    {
        return $this->bookingId;
    }

    public function type(): BookingType
    {
        return $this->type;
    }

    public function slotId(): ?SlotId
    {
        return $this->slotId;
    }

    public function aggregateId(): string
    {
        return $this->bookingId->toString();
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function eventName(): string
    {
        return 'booking.cancelled';
    }

    public function payload(): array
    {
        return [
            'booking_id' => $this->bookingId->toString(),
            'type' => $this->type->value,
            'slot_id' => $this->slotId?->toString(),
            'occurred_at' => $this->occurredAt->format(DATE_ATOM),
        ];
    }
}
