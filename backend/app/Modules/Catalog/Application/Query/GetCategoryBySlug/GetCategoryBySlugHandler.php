<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Query\GetCategoryBySlug;

use App\Modules\Catalog\Application\DTO\CategoryDTO;
use App\Modules\Catalog\Application\DTO\CategoryWithSubcategoriesDTO;
use App\Modules\Catalog\Application\DTO\SubcategoryDTO;
use Illuminate\Support\Facades\DB;

/**
 * Возвращает категорию с подкатегориями по slug, либо null если не найдена.
 */
final readonly class GetCategoryBySlugHandler
{
    public function handle(GetCategoryBySlugQuery $query): ?CategoryWithSubcategoriesDTO
    {
        $category = DB::table('categories')
            ->where('slug', $query->slug)
            ->first();

        if ($category === null) {
            return null;
        }

        $categoryId = (string) $category->id;

        $subRows = DB::table('subcategories')
            ->where('category_id', $categoryId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $subs = [];
        foreach ($subRows as $subRow) {
            $subs[] = new SubcategoryDTO(
                id: (string) $subRow->id,
                categoryId: $categoryId,
                name: (string) $subRow->name,
                slug: (string) $subRow->slug,
                sortOrder: (int) $subRow->sort_order,
            );
        }

        return new CategoryWithSubcategoriesDTO(
            category: new CategoryDTO(
                id: $categoryId,
                name: (string) $category->name,
                slug: (string) $category->slug,
                sortOrder: (int) $category->sort_order,
            ),
            subcategories: $subs,
        );
    }
}
