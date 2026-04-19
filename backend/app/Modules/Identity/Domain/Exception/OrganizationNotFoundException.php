<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Exception;

use App\Modules\Identity\Domain\ValueObject\OrganizationId;
use App\Modules\Identity\Domain\ValueObject\OrganizationSlug;
use App\Shared\Domain\Exception\DomainException;

/**
 * Бросается, когда Organization не найдена по id / slug.
 */
final class OrganizationNotFoundException extends DomainException
{
    public static function byId(OrganizationId $id): self
    {
        return new self(sprintf('Organization with id "%s" not found', $id->toString()));
    }

    public static function bySlug(OrganizationSlug $slug): self
    {
        return new self(sprintf('Organization with slug "%s" not found', $slug->toString()));
    }

    public function errorCode(): string
    {
        return 'ORG_NOT_FOUND';
    }
}
