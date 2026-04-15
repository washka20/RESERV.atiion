<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Entity;

use App\Modules\Catalog\Domain\ValueObject\CategoryId;
use App\Modules\Catalog\Domain\ValueObject\SubcategoryId;

/**
 * Подкатегория. Child entity внутри агрегата Category.
 *
 * НЕ aggregate root — domain events публикует владеющая категория.
 */
final class Subcategory
{
    private function __construct(
        private readonly SubcategoryId $id,
        private readonly CategoryId $categoryId,
        private string $name,
        private string $slug,
        private int $sortOrder,
    ) {}

    public static function create(
        SubcategoryId $id,
        CategoryId $categoryId,
        string $name,
        string $slug,
        int $sortOrder,
    ): self {
        return new self($id, $categoryId, $name, $slug, $sortOrder);
    }

    /**
     * Восстанавливает подкатегорию из хранилища.
     */
    public static function restore(
        SubcategoryId $id,
        CategoryId $categoryId,
        string $name,
        string $slug,
        int $sortOrder,
    ): self {
        return new self($id, $categoryId, $name, $slug, $sortOrder);
    }

    public function changeName(string $name): void
    {
        $this->name = $name;
    }

    public function changeSortOrder(int $sortOrder): void
    {
        $this->sortOrder = $sortOrder;
    }

    public function id(): SubcategoryId
    {
        return $this->id;
    }

    public function categoryId(): CategoryId
    {
        return $this->categoryId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function slug(): string
    {
        return $this->slug;
    }

    public function sortOrder(): int
    {
        return $this->sortOrder;
    }
}
