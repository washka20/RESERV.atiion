<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Command\DeleteCategory;

final readonly class DeleteCategoryCommand
{
    public function __construct(public string $categoryId) {}
}
