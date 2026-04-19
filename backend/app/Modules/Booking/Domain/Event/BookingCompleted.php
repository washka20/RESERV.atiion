<?php

declare(strict_types=1);

namespace App\Modules\Booking\Domain\Event;

use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Shared\Domain\DomainEvent;
use DateTimeImmutable;

/**
 * Бронирование завершено (услуга оказана).
 */
final readonly class BookingCompleted implements DomainEvent
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
        return 'booking.completed';
    }

    public function payload(): array
    {
        return [
            'booking_id' => $this->bookingId->toString(),
            'occurred_at' => $this->occurredAt->format(DATE_ATOM),
        ];
    }

    public static function fromPayload(array $payload): self
    {
        return new self(
            new BookingId((string) $payload['booking_id']),
            new DateTimeImmutable((string) $payload['occurred_at']),
        );
    }
}
