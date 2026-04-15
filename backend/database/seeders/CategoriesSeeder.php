<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Catalog\Application\Command\CreateCategory\CreateCategoryCommand;
use App\Shared\Application\Bus\CommandBusInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Сидер категорий каталога.
 *
 * Создаёт три базовые категории через CommandBus — доменный flow
 * с публикацией CategoryCreated events.
 */
final class CategoriesSeeder extends Seeder
{
    public function __construct(private readonly CommandBusInterface $commandBus) {}

    public function run(): void
    {
        $categories = [
            ['name' => 'Стрижки', 'slug' => 'haircuts', 'sortOrder' => 10],
            ['name' => 'Отели', 'slug' => 'hotels', 'sortOrder' => 20],
            ['name' => 'Консультации', 'slug' => 'consultations', 'sortOrder' => 30],
        ];

        foreach ($categories as $c) {
            if (DB::table('categories')->where('slug', $c['slug'])->exists()) {
                continue;
            }

            $this->commandBus->dispatch(new CreateCategoryCommand(
                name: $c['name'],
                slug: $c['slug'],
                sortOrder: $c['sortOrder'],
            ));
        }
    }
}
