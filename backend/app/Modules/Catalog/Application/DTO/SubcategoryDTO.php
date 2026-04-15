<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\DTO;

/**
 * Подкатегория для read-моделей.
 */
final readonly class SubcategoryDTO
{
    public function __construct(
        public string $id,
        public string $categoryId,
        public string $name,
        public string $slug,
        public int $sortOrder,
    ) {}
}
