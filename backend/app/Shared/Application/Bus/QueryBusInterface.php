<?php

declare(strict_types=1);

namespace App\Shared\Application\Bus;

interface QueryBusInterface
{
    /**
     * Ask a query and return the result (DTO | array | scalar).
     */
    public function ask(object $query): mixed;
}
