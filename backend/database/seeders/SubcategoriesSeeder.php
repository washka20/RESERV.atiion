<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Catalog\Application\Command\CreateSubcategory\CreateSubcategoryCommand;
use App\Shared\Application\Bus\CommandBusInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Сидер подкатегорий каталога.
 *
 * Создаёт подкатегории под "Стрижки" и "Отели". Идентификаторы родительских
 * категорий берутся lookup-ом по slug.
 */
final class SubcategoriesSeeder extends Seeder
{
    public function __construct(private readonly CommandBusInterface $commandBus) {}

    public function run(): void
    {
        $haircutsId = $this->categoryIdBySlug('haircuts');
        $hotelsId = $this->categoryIdBySlug('hotels');

        $subcategories = [
            ['categoryId' => $haircutsId, 'name' => 'Мужские', 'slug' => 'men-haircuts', 'sortOrder' => 10],
            ['categoryId' => $haircutsId, 'name' => 'Женские', 'slug' => 'women-haircuts', 'sortOrder' => 20],
            ['categoryId' => $hotelsId, 'name' => 'Стандарт', 'slug' => 'standard-rooms', 'sortOrder' => 10],
            ['categoryId' => $hotelsId, 'name' => 'Люкс', 'slug' => 'luxury-rooms', 'sortOrder' => 20],
        ];

        foreach ($subcategories as $s) {
            if (DB::table('subcategories')->where('slug', $s['slug'])->exists()) {
                continue;
            }

            $this->commandBus->dispatch(new CreateSubcategoryCommand(
                categoryId: $s['categoryId'],
                name: $s['name'],
                slug: $s['slug'],
                sortOrder: $s['sortOrder'],
            ));
        }
    }

    private function categoryIdBySlug(string $slug): string
    {
        $id = DB::table('categories')->where('slug', $slug)->value('id');

        if ($id === null) {
            throw new RuntimeException("Category with slug '{$slug}' not found. Run CategoriesSeeder first.");
        }

        return (string) $id;
    }
}
