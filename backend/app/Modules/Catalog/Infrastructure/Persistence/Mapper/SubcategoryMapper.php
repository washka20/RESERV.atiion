<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Persistence\Mapper;

use App\Modules\Catalog\Domain\Entity\Subcategory;
use App\Modules\Catalog\Domain\ValueObject\CategoryId;
use App\Modules\Catalog\Domain\ValueObject\SubcategoryId;
use App\Modules\Catalog\Infrastructure\Persistence\Model\SubcategoryModel;
use DateTimeImmutable;

/**
 * Маппер Subcategory ↔ SubcategoryModel.
 */
final class SubcategoryMapper
{
    public static function toDomain(SubcategoryModel $model): Subcategory
    {
        return Subcategory::restore(
            new SubcategoryId($model->id),
            new CategoryId($model->category_id),
            $model->name,
            $model->slug,
            (int) $model->sort_order,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function toPersistence(Subcategory $subcategory): array
    {
        $now = new DateTimeImmutable;

        return [
            'id' => $subcategory->id()->toString(),
            'category_id' => $subcategory->categoryId()->toString(),
            'name' => $subcategory->name(),
            'slug' => $subcategory->slug(),
            'sort_order' => $subcategory->sortOrder(),
            'updated_at' => $now,
        ];
    }
}
