<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Persistence\Mapper;

use App\Modules\Catalog\Domain\Entity\Category;
use App\Modules\Catalog\Domain\Entity\Subcategory;
use App\Modules\Catalog\Domain\ValueObject\CategoryId;
use App\Modules\Catalog\Infrastructure\Persistence\Model\CategoryModel;
use App\Modules\Catalog\Infrastructure\Persistence\Model\SubcategoryModel;
use DateTimeImmutable;

/**
 * Маппер Category ↔ CategoryModel. Восстанавливает коллекцию Subcategory из relations.
 */
final class CategoryMapper
{
    public static function toDomain(CategoryModel $model): Category
    {
        /** @var list<Subcategory> $subcategories */
        $subcategories = [];
        foreach ($model->subcategories as $subModel) {
            /** @var SubcategoryModel $subModel */
            $subcategories[] = SubcategoryMapper::toDomain($subModel);
        }

        return Category::restore(
            new CategoryId($model->id),
            $model->name,
            $model->slug,
            (int) $model->sort_order,
            $subcategories,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function toPersistence(Category $category): array
    {
        $now = new DateTimeImmutable;

        return [
            'id' => $category->id()->toString(),
            'name' => $category->name(),
            'slug' => $category->slug(),
            'sort_order' => $category->sortOrder(),
            'updated_at' => $now,
        ];
    }
}
