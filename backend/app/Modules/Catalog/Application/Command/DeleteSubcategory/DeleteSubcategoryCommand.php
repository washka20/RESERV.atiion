<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Command\DeleteSubcategory;

final readonly class DeleteSubcategoryCommand
{
    public function __construct(
        public string $categoryId,
        public string $subcategoryId,
    ) {}
}
