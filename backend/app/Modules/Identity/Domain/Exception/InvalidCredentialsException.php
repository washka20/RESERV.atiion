<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

final class InvalidCredentialsException extends DomainException
{
    public function errorCode(): string
    {
        return 'IDENTITY_INVALID_CREDENTIALS';
    }
}
