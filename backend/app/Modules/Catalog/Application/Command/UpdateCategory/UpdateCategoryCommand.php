<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Command\UpdateCategory;

final readonly class UpdateCategoryCommand
{
    public function __construct(
        public string $categoryId,
        public string $name,
        public int $sortOrder,
    ) {}
}
