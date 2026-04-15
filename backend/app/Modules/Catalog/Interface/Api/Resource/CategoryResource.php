<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Interface\Api\Resource;

use App\Modules\Catalog\Application\DTO\CategoryWithSubcategoriesDTO;

/**
 * Сериализует CategoryWithSubcategoriesDTO с вложенными подкатегориями.
 */
final class CategoryResource
{
    /**
     * @return array<string, mixed>
     */
    public static function fromDTO(CategoryWithSubcategoriesDTO $dto): array
    {
        $subcategories = [];
        foreach ($dto->subcategories as $sub) {
            $subcategories[] = [
                'id' => $sub->id,
                'name' => $sub->name,
                'slug' => $sub->slug,
                'sort_order' => $sub->sortOrder,
            ];
        }

        return [
            'id' => $dto->category->id,
            'name' => $dto->category->name,
            'slug' => $dto->category->slug,
            'sort_order' => $dto->category->sortOrder,
            'subcategories' => $subcategories,
        ];
    }
}
