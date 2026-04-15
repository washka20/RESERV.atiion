<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Persistence\Repository;

use App\Modules\Catalog\Domain\Entity\Category;
use App\Modules\Catalog\Domain\Entity\Subcategory;
use App\Modules\Catalog\Domain\Exception\CategoryNotFoundException;
use App\Modules\Catalog\Domain\Repository\CategoryRepositoryInterface;
use App\Modules\Catalog\Domain\ValueObject\CategoryId;
use App\Modules\Catalog\Infrastructure\Persistence\Mapper\CategoryMapper;
use App\Modules\Catalog\Infrastructure\Persistence\Mapper\SubcategoryMapper;
use App\Modules\Catalog\Infrastructure\Persistence\Model\CategoryModel;
use App\Modules\Catalog\Infrastructure\Persistence\Model\SubcategoryModel;
use Illuminate\Support\Facades\DB;

final class EloquentCategoryRepository implements CategoryRepositoryInterface
{
    public function save(Category $category): void
    {
        DB::transaction(function () use ($category): void {
            $data = CategoryMapper::toPersistence($category);
            $data['created_at'] = $data['created_at'] ?? now();

            CategoryModel::query()->updateOrInsert(
                ['id' => $data['id']],
                $data,
            );

            $this->syncSubcategories($category);
        });
    }

    public function findById(CategoryId $id): ?Category
    {
        $model = CategoryModel::with('subcategories')->find($id->toString());

        return $model !== null ? CategoryMapper::toDomain($model) : null;
    }

    public function findByIdOrFail(CategoryId $id): Category
    {
        return $this->findById($id) ?? throw CategoryNotFoundException::byId($id);
    }

    public function findBySlug(string $slug): ?Category
    {
        $model = CategoryModel::with('subcategories')->where('slug', $slug)->first();

        return $model !== null ? CategoryMapper::toDomain($model) : null;
    }

    public function delete(CategoryId $id): void
    {
        CategoryModel::query()->where('id', $id->toString())->delete();
    }

    /**
     * @return list<Category>
     */
    public function all(): array
    {
        $models = CategoryModel::with('subcategories')
            ->orderBy('sort_order')
            ->get();

        /** @var list<Category> $categories */
        $categories = [];
        foreach ($models as $model) {
            $categories[] = CategoryMapper::toDomain($model);
        }

        return $categories;
    }

    /**
     * Синхронизирует subcategories: удаляет отсутствующие, добавляет/обновляет текущие.
     */
    private function syncSubcategories(Category $category): void
    {
        $categoryId = $category->id()->toString();

        $currentIds = array_map(
            static fn (Subcategory $s): string => $s->id()->toString(),
            $category->subcategories(),
        );

        $query = SubcategoryModel::query()->where('category_id', $categoryId);
        if ($currentIds !== []) {
            $query->whereNotIn('id', $currentIds);
        }
        $query->delete();

        foreach ($category->subcategories() as $subcategory) {
            $data = SubcategoryMapper::toPersistence($subcategory);
            $data['created_at'] = $data['created_at'] ?? now();

            SubcategoryModel::query()->updateOrInsert(
                ['id' => $data['id']],
                $data,
            );
        }
    }
}
