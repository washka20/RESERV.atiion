<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Query\GetCategoryBySlug;

/**
 * Запрос категории с подкатегориями по slug.
 */
final readonly class GetCategoryBySlugQuery
{
    public function __construct(
        public string $slug,
    ) {}
}
