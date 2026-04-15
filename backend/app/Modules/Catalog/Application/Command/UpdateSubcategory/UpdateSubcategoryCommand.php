<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Command\UpdateSubcategory;

final readonly class UpdateSubcategoryCommand
{
    public function __construct(
        public string $categoryId,
        public string $subcategoryId,
        public string $name,
        public int $sortOrder,
    ) {}
}
