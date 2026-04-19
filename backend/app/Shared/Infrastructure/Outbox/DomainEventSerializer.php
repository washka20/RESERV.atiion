<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Outbox;

use App\Shared\Domain\DomainEvent;
use DateTimeInterface;
use RuntimeException;

/**
 * Сериализует/десериализует DomainEvent в array-payload для хранения в outbox_messages.
 *
 * Round-trip: toPayload($event) → fromPayload($payload) должен давать эквивалентное событие
 * (по результату ->payload()). Используется Outbox writer (запись) и worker (replay).
 */
final class DomainEventSerializer
{
    /**
     * @return array<string, mixed>
     */
    public function toPayload(DomainEvent $event): array
    {
        return [
            'event_class' => $event::class,
            'aggregate_id' => $event->aggregateId(),
            'occurred_at' => $event->occurredAt()->format(DateTimeInterface::RFC3339_EXTENDED),
            'event_name' => $event->eventName(),
            'data' => $event->payload(),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function fromPayload(array $payload): DomainEvent
    {
        $class = $payload['event_class'] ?? throw new RuntimeException('outbox payload missing event_class');
        if (! is_string($class) || ! class_exists($class) || ! is_subclass_of($class, DomainEvent::class)) {
            throw new RuntimeException("outbox payload has unknown event class: {$class}");
        }

        /** @var array<string, mixed> $data */
        $data = $payload['data'] ?? [];

        return $class::fromPayload($data);
    }
}
