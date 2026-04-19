<?php

declare(strict_types=1);

namespace App\Modules\Booking\Domain\Event;

use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use App\Shared\Domain\DomainEvent;
use DateTimeImmutable;

/**
 * Пачка временных слотов сгенерирована для услуги (обычно из Filament admin page).
 */
final readonly class TimeSlotGenerated implements DomainEvent
{
    public function __construct(
        private ServiceId $serviceId,
        private DateTimeImmutable $from,
        private DateTimeImmutable $to,
        private int $count,
        private DateTimeImmutable $occurredAt,
    ) {}

    public function serviceId(): ServiceId
    {
        return $this->serviceId;
    }

    public function from(): DateTimeImmutable
    {
        return $this->from;
    }

    public function to(): DateTimeImmutable
    {
        return $this->to;
    }

    public function count(): int
    {
        return $this->count;
    }

    public function aggregateId(): string
    {
        return $this->serviceId->toString();
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function eventName(): string
    {
        return 'booking.time_slots.generated';
    }

    public function payload(): array
    {
        return [
            'service_id' => $this->serviceId->toString(),
            'from' => $this->from->format(DATE_ATOM),
            'to' => $this->to->format(DATE_ATOM),
            'count' => $this->count,
            'occurred_at' => $this->occurredAt->format(DATE_ATOM),
        ];
    }

    public static function fromPayload(array $payload): self
    {
        return new self(
            new ServiceId((string) $payload['service_id']),
            new DateTimeImmutable((string) $payload['from']),
            new DateTimeImmutable((string) $payload['to']),
            (int) $payload['count'],
            new DateTimeImmutable((string) $payload['occurred_at']),
        );
    }
}
