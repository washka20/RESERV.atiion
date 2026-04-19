<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Outbox;

use App\Shared\Domain\DomainEvent;
use Illuminate\Support\Str;

/**
 * Пишет DomainEvent в outbox_messages как row со статусом pending.
 *
 * Вызывается из OutboxPublisher::publish() с reliable=true. Транзакционно согласован
 * с бизнес-операцией — если commit прошёл, message в outbox гарантированно есть
 * (transactional outbox pattern).
 */
final class OutboxWriter
{
    public function __construct(private readonly DomainEventSerializer $serializer) {}

    public function write(DomainEvent $event): void
    {
        OutboxMessageModel::create([
            'id' => (string) Str::uuid(),
            'aggregate_id' => $event->aggregateId(),
            'event_type' => $event->eventName(),
            'payload' => $this->serializer->toPayload($event),
            'status' => 'pending',
            'retry_count' => 0,
        ]);
    }
}
