<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Exception;

use App\Modules\Identity\Domain\ValueObject\MembershipId;
use App\Shared\Domain\Exception\DomainException;

/**
 * Бросается, когда Membership не найден по id.
 */
final class MembershipNotFoundException extends DomainException
{
    public static function byId(MembershipId $id): self
    {
        return new self(sprintf('Membership with id "%s" not found', $id->toString()));
    }

    public function errorCode(): string
    {
        return 'MEMBERSHIP_NOT_FOUND';
    }
}
