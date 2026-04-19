<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Exception;

use App\Modules\Identity\Domain\ValueObject\OrganizationSlug;
use App\Shared\Domain\Exception\DomainException;

/**
 * Бросается при попытке создать Organization с уже существующим slug'ом.
 * SlugGenerator должен авто-генерировать уникальный slug, это защита на случай
 * race condition или ручной передачи slug'а в command.
 */
final class DuplicateSlugException extends DomainException
{
    public static function forSlug(OrganizationSlug $slug): self
    {
        return new self(sprintf('Organization slug "%s" is already taken', $slug->toString()));
    }

    public function errorCode(): string
    {
        return 'ORG_DUPLICATE_SLUG';
    }
}
