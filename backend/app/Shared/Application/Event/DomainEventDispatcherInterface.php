<?php

declare(strict_types=1);

namespace App\Shared\Application\Event;

use App\Shared\Domain\DomainEvent;

interface DomainEventDispatcherInterface
{
    public function dispatch(DomainEvent $event): void;

    /**
     * @param  iterable<DomainEvent>  $events
     */
    public function dispatchAll(iterable $events): void;
}
