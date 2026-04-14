<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus;

use App\Shared\Application\Bus\CommandBusInterface;
use Illuminate\Contracts\Container\Container;
use RuntimeException;

/**
 * Реализует CommandBusInterface через IoC-контейнер Laravel.
 *
 * Разрешает handler по соглашению: FooCommand → FooHandler.
 */
final class LaravelCommandBus implements CommandBusInterface
{
    public function __construct(private readonly Container $container) {}

    public function dispatch(object $command): mixed
    {
        $handlerClass = $this->resolveHandlerClass($command);
        $handler = $this->container->make($handlerClass);

        return $handler->handle($command);
    }

    private function resolveHandlerClass(object $command): string
    {
        $class = $command::class;
        $candidate = preg_replace('/Command$/', 'Handler', $class, 1, $count);

        if ($count === 0 || ! class_exists((string) $candidate)) {
            throw new RuntimeException(sprintf(
                'Handler for command %s not found (expected %s)',
                $class,
                (string) $candidate
            ));
        }

        return (string) $candidate;
    }
}
