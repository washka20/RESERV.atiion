<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Exception;

use App\Modules\Identity\Domain\ValueObject\OrganizationId;
use App\Shared\Domain\Exception\DomainException;

/**
 * Бросается при операциях над уже заархивированной организацией
 * (double-archive, update archived и т.д.).
 */
final class OrganizationArchivedException extends DomainException
{
    public static function forId(OrganizationId $id): self
    {
        return new self(sprintf('Organization "%s" is archived and cannot be modified', $id->toString()));
    }

    public function errorCode(): string
    {
        return 'ORG_ARCHIVED';
    }
}
