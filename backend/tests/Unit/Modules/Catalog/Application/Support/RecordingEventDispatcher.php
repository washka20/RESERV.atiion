<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Catalog\Application\Support;

use App\Shared\Application\Event\DomainEventDispatcherInterface;
use App\Shared\Domain\DomainEvent;

final class RecordingEventDispatcher implements DomainEventDispatcherInterface
{
    /** @var list<DomainEvent> */
    public array $events = [];

    public function dispatch(DomainEvent $event): void
    {
        $this->events[] = $event;
    }

    public function dispatchAll(iterable $events): void
    {
        foreach ($events as $event) {
            $this->dispatch($event);
        }
    }
}
