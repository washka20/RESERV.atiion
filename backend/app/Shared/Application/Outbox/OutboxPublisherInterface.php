<?php

declare(strict_types=1);

namespace App\Shared\Application\Outbox;

use App\Shared\Domain\DomainEvent;

interface OutboxPublisherInterface
{
    /**
     * Publish a domain event.
     *
     * @param  bool  $reliable  if true — persists to outbox_messages (at-least-once delivery).
     *                          if false — dispatches via Laravel events (fire-and-forget).
     */
    public function publish(DomainEvent $event, bool $reliable = false): void;
}
