<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

use RuntimeException;

abstract class DomainException extends RuntimeException
{
    /**
     * Машинночитаемый код ошибки для API envelope.
     * Пример: "BOOKING_SLOT_UNAVAILABLE".
     */
    abstract public function errorCode(): string;
}
