<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Command\CreateSubcategory;

final readonly class CreateSubcategoryCommand
{
    public function __construct(
        public string $categoryId,
        public string $name,
        public string $slug,
        public int $sortOrder = 0,
    ) {}
}
