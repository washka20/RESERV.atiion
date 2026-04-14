<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Event;

use App\Shared\Domain\DomainEvent;
use Illuminate\Contracts\Events\Dispatcher;

final class LaravelDomainEventDispatcher
{
    public function __construct(private readonly Dispatcher $events) {}

    /**
     * @param  iterable<DomainEvent>  $events
     */
    public function dispatchAll(iterable $events): void
    {
        foreach ($events as $event) {
            $this->events->dispatch($event->eventName(), [$event]);
            $this->events->dispatch($event);
        }
    }
}
