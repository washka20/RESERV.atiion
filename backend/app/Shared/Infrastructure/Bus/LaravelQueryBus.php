<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus;

use App\Shared\Application\Bus\QueryBusInterface;
use Illuminate\Contracts\Container\Container;
use RuntimeException;

/**
 * Реализует QueryBusInterface через IoC-контейнер Laravel.
 *
 * Разрешает handler по соглашению: FooQuery → FooHandler.
 */
final class LaravelQueryBus implements QueryBusInterface
{
    public function __construct(private readonly Container $container) {}

    public function ask(object $query): mixed
    {
        $handlerClass = $this->resolveHandlerClass($query);
        $handler = $this->container->make($handlerClass);

        return $handler->handle($query);
    }

    private function resolveHandlerClass(object $query): string
    {
        $class = $query::class;
        $candidate = preg_replace('/Query$/', 'Handler', $class, 1, $count);

        if ($count === 0 || ! class_exists((string) $candidate)) {
            throw new RuntimeException(sprintf(
                'Handler for query %s not found (expected %s)',
                $class,
                (string) $candidate
            ));
        }

        return (string) $candidate;
    }
}
