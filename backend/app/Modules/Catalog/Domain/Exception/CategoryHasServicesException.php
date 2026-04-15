<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Exception;

use App\Modules\Catalog\Domain\ValueObject\CategoryId;
use App\Shared\Domain\Exception\DomainException;

/**
 * Бросается при попытке удалить категорию, к которой привязаны услуги.
 */
final class CategoryHasServicesException extends DomainException
{
    public static function forCategory(CategoryId $id): self
    {
        return new self(sprintf('Category "%s" has attached services and cannot be deleted', $id->toString()));
    }

    public function errorCode(): string
    {
        return 'CATALOG_CATEGORY_HAS_SERVICES';
    }
}
