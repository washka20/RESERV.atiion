<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Event;

use App\Shared\Application\Event\DomainEventDispatcherInterface;
use App\Shared\Domain\DomainEvent;
use Illuminate\Contracts\Events\Dispatcher;

final class LaravelDomainEventDispatcher implements DomainEventDispatcherInterface
{
    public function __construct(private readonly Dispatcher $events) {}

    public function dispatch(DomainEvent $event): void
    {
        $this->events->dispatch($event->eventName(), [$event]);
        $this->events->dispatch($event);
    }

    public function dispatchAll(iterable $events): void
    {
        foreach ($events as $event) {
            $this->dispatch($event);
        }
    }
}
