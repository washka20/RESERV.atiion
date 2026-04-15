<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Command\CreateCategory;

final readonly class CreateCategoryCommand
{
    public function __construct(
        public string $name,
        public string $slug,
        public int $sortOrder = 0,
    ) {}
}
