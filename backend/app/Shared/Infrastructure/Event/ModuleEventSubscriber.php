<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Event;

use Illuminate\Contracts\Events\Dispatcher;

/**
 * Helper for module ServiceProviders to register domain-event listeners declaratively.
 *
 * Usage in Module\Provider:
 *   ModuleEventSubscriber::register($this->app['events'], [
 *       SomeEvent::class => [FirstListener::class, SecondListener::class],
 *   ]);
 */
final class ModuleEventSubscriber
{
    /**
     * @param  array<class-string, list<class-string>>  $listeners
     */
    public static function register(Dispatcher $events, array $listeners): void
    {
        foreach ($listeners as $eventClass => $listenerClasses) {
            foreach ($listenerClasses as $listenerClass) {
                $events->listen($eventClass, $listenerClass);
            }
        }
    }
}
