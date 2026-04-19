<?php

declare(strict_types=1);

namespace App\Shared\Domain;

use DateTimeImmutable;

interface DomainEvent
{
    public function aggregateId(): string;

    public function occurredAt(): DateTimeImmutable;

    public function eventName(): string;

    /**
     * @return array<string, mixed>
     */
    public function payload(): array;

    /**
     * Десериализует событие из payload'а (для Outbox replay).
     *
     * @param  array<string, mixed>  $payload
     */
    public static function fromPayload(array $payload): self;
}
