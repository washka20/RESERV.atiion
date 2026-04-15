<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Query\ListCategories;

use App\Modules\Catalog\Application\DTO\CategoryDTO;
use App\Modules\Catalog\Application\DTO\CategoryWithSubcategoriesDTO;
use App\Modules\Catalog\Application\DTO\SubcategoryDTO;
use Illuminate\Support\Facades\DB;

/**
 * Возвращает все категории с вложенными подкатегориями, отсортированные по sort_order.
 */
final readonly class ListCategoriesHandler
{
    /**
     * @return list<CategoryWithSubcategoriesDTO>
     */
    public function handle(ListCategoriesQuery $query): array
    {
        $categories = DB::table('categories')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $subcategoryRows = DB::table('subcategories')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->groupBy(static fn ($row): string => (string) $row->category_id);

        $result = [];
        foreach ($categories as $category) {
            $categoryId = (string) $category->id;
            $subs = [];

            if ($subcategoryRows->has($categoryId)) {
                foreach ($subcategoryRows->get($categoryId) as $subRow) {
                    $subs[] = new SubcategoryDTO(
                        id: (string) $subRow->id,
                        categoryId: $categoryId,
                        name: (string) $subRow->name,
                        slug: (string) $subRow->slug,
                        sortOrder: (int) $subRow->sort_order,
                    );
                }
            }

            $result[] = new CategoryWithSubcategoriesDTO(
                category: new CategoryDTO(
                    id: $categoryId,
                    name: (string) $category->name,
                    slug: (string) $category->slug,
                    sortOrder: (int) $category->sort_order,
                ),
                subcategories: $subs,
            );
        }

        return $result;
    }
}
