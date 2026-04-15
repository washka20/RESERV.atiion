<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\DTO;

/**
 * Категория для read-моделей.
 */
final readonly class CategoryDTO
{
    public function __construct(
        public string $id,
        public string $name,
        public string $slug,
        public int $sortOrder,
    ) {}
}
