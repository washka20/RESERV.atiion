<?php

declare(strict_types=1);

namespace App\Shared\Application\Bus;

interface CommandBusInterface
{
    /**
     * Dispatch a command and return handler result.
     * Command class FQN resolves to `<Command>Handler` FQN by convention.
     */
    public function dispatch(object $command): mixed;
}
