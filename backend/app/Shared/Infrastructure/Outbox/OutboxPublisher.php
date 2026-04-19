<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Outbox;

use App\Shared\Application\Outbox\OutboxPublisherInterface;
use App\Shared\Domain\DomainEvent;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * Публикация domain events с двумя режимами доставки:
 *  - reliable=true  — транзакционная запись в outbox_messages (at-least-once через worker).
 *  - reliable=false — fire-and-forget через Laravel dispatcher (синхронные listeners).
 *
 * Выбор режима остаётся за handler'ом: например, PaymentReceived → reliable,
 * TimeSlotReserved внутри той же транзакции → не reliable.
 */
final class OutboxPublisher implements OutboxPublisherInterface
{
    public function __construct(
        private readonly OutboxWriter $writer,
        private readonly Dispatcher $events,
    ) {}

    public function publish(DomainEvent $event, bool $reliable = false): void
    {
        if ($reliable) {
            $this->writer->write($event);

            return;
        }

        $this->events->dispatch($event->eventName(), [$event]);
        $this->events->dispatch($event);
    }
}
