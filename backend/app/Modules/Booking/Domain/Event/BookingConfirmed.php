<?php

declare(strict_types=1);

namespace App\Modules\Booking\Domain\Event;

use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Shared\Domain\DomainEvent;
use DateTimeImmutable;

/**
 * Бронирование подтверждено администратором (переход PENDING -> CONFIRMED).
 */
final readonly class BookingConfirmed implements DomainEvent
{
    public function __construct(
        private BookingId $bookingId,
        private DateTimeImmutable $occurredAt,
    ) {}

    public function bookingId(): BookingId
    {
        return $this->bookingId;
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
        return 'booking.confirmed';
    }

    public function payload(): array
    {
        return [
            'booking_id' => $this->bookingId->toString(),
            'occurred_at' => $this->occurredAt->format(DATE_ATOM),
        ];
    }
}
