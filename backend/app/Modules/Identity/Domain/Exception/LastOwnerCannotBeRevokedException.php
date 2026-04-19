<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Exception;

use App\Modules\Identity\Domain\ValueObject\OrganizationId;
use App\Shared\Domain\Exception\DomainException;

/**
 * Бросается при попытке revoke / demote последнего owner'а организации.
 *
 * Бизнес-инвариант: в каждой active Organization должен быть как минимум один OWNER.
 * Чтобы revoke себя или поменять роль — сначала нужно promote кого-то ещё в owner.
 */
final class LastOwnerCannotBeRevokedException extends DomainException
{
    public static function forOrganization(OrganizationId $organizationId): self
    {
        return new self(sprintf(
            'Cannot revoke the last owner of organization "%s" — promote another member to owner first',
            $organizationId->toString(),
        ));
    }

    public function errorCode(): string
    {
        return 'MEMBERSHIP_LAST_OWNER';
    }
}
