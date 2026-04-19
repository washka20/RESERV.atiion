<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Exception;

use App\Modules\Identity\Domain\ValueObject\Email;
use App\Modules\Identity\Domain\ValueObject\UserId;
use App\Shared\Domain\Exception\DomainException;

/**
 * Бросается, когда User не найден по id / email.
 */
final class UserNotFoundException extends DomainException
{
    public static function byId(UserId $id): self
    {
        return new self(sprintf('User with id "%s" not found', $id->toString()));
    }

    public static function byEmail(Email $email): self
    {
        return new self(sprintf('User with email "%s" not found', $email->value()));
    }

    public function errorCode(): string
    {
        return 'USER_NOT_FOUND';
    }
}
