<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Repository;

use App\Modules\Catalog\Domain\Entity\Category;
use App\Modules\Catalog\Domain\Exception\CategoryNotFoundException;
use App\Modules\Catalog\Domain\ValueObject\CategoryId;

interface CategoryRepositoryInterface
{
    public function save(Category $category): void;

    public function findById(CategoryId $id): ?Category;

    /**
     * @throws CategoryNotFoundException если категории нет
     */
    public function findByIdOrFail(CategoryId $id): Category;

    public function findBySlug(string $slug): ?Category;

    public function delete(CategoryId $id): void;

    /**
     * @return list<Category>
     */
    public function all(): array;
}
