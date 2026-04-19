<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Exception;

use App\Modules\Identity\Domain\ValueObject\OrganizationId;
use App\Modules\Identity\Domain\ValueObject\UserId;
use App\Shared\Domain\Exception\DomainException;

/**
 * Бросается при попытке создать дубль Membership (user уже член организации).
 * Повторный invite должен менять роль existing членства, не создавать новый.
 */
final class MembershipAlreadyExistsException extends DomainException
{
    public static function forPair(UserId $userId, OrganizationId $organizationId): self
    {
        return new self(sprintf(
            'User "%s" already has a membership in organization "%s"',
            $userId->toString(),
            $organizationId->toString(),
        ));
    }

    public function errorCode(): string
    {
        return 'MEMBERSHIP_ALREADY_EXISTS';
    }
}
