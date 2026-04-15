<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\DTO;

/**
 * Категория с вложенными подкатегориями — для деревьев каталога.
 */
final readonly class CategoryWithSubcategoriesDTO
{
    /**
     * @param  list<SubcategoryDTO>  $subcategories
     */
    public function __construct(
        public CategoryDTO $category,
        public array $subcategories,
    ) {}
}
