<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Exception;

use App\Modules\Catalog\Domain\ValueObject\CategoryId;
use App\Shared\Domain\Exception\DomainException;

/**
 * Бросается, когда категория не найдена по идентификатору.
 */
final class CategoryNotFoundException extends DomainException
{
    public static function byId(CategoryId $id): self
    {
        return new self(sprintf('Category with id "%s" not found', $id->toString()));
    }

    public function errorCode(): string
    {
        return 'CATALOG_CATEGORY_NOT_FOUND';
    }
}
