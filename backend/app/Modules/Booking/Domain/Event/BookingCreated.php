<?php

declare(strict_types=1);

namespace App\Modules\Booking\Domain\Event;

use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Modules\Booking\Domain\ValueObject\BookingType;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use App\Modules\Identity\Domain\ValueObject\UserId;
use App\Shared\Domain\DomainEvent;
use DateTimeImmutable;

/**
 * Бронирование создано (в статусе PENDING).
 */
final readonly class BookingCreated implements DomainEvent
{
    public function __construct(
        private BookingId $bookingId,
        private UserId $userId,
        private ServiceId $serviceId,
        private BookingType $type,
        private DateTimeImmutable $occurredAt,
    ) {}

    public function bookingId(): BookingId
    {
        return $this->bookingId;
    }

    public function userId(): UserId
    {
        return $this->userId;
    }

    public function serviceId(): ServiceId
    {
        return $this->serviceId;
    }

    public function type(): BookingType
    {
        return $this->type;
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
        return 'booking.created';
    }

    public function payload(): array
    {
        return [
            'booking_id' => $this->bookingId->toString(),
            'user_id' => $this->userId->toString(),
            'service_id' => $this->serviceId->toString(),
            'type' => $this->type->value,
            'occurred_at' => $this->occurredAt->format(DATE_ATOM),
        ];
    }

    public static function fromPayload(array $payload): self
    {
        return new self(
            new BookingId((string) $payload['booking_id']),
            new UserId((string) $payload['user_id']),
            new ServiceId((string) $payload['service_id']),
            BookingType::from((string) $payload['type']),
            new DateTimeImmutable((string) $payload['occurred_at']),
        );
    }
}
