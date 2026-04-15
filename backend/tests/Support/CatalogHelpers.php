<?php

declare(strict_types=1);

use App\Modules\Catalog\Application\Query\ListServices\ListServicesHandler;
use App\Modules\Catalog\Domain\Entity\Service;
use App\Modules\Catalog\Domain\Repository\ServiceRepositoryInterface;
use App\Modules\Catalog\Domain\ValueObject\CategoryId;
use App\Modules\Catalog\Domain\ValueObject\Duration;
use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use App\Modules\Catalog\Domain\ValueObject\SubcategoryId;
use App\Modules\Catalog\Infrastructure\Persistence\Model\CategoryModel;
use App\Modules\Catalog\Infrastructure\Persistence\Model\SubcategoryModel;

/*
 * Catalog test helpers — глобальные функции для Feature-тестов.
 *
 * Файл подключается автоматически через composer.json autoload-dev "files".
 * Paratest запускает тесты в отдельных процессах, поэтому функции определённые
 * внутри отдельного *Test.php файла не видны в других тестах. "files"-autoload
 * гарантирует загрузку в каждом процессе.
 */

if (! function_exists('insertCategory')) {
    function insertCategory(string $name = 'Beauty', int $sortOrder = 0): CategoryId
    {
        $id = CategoryId::generate();
        CategoryModel::query()->insert([
            'id' => $id->toString(),
            'name' => $name,
            'slug' => strtolower($name).'-'.substr($id->toString(), 0, 8),
            'sort_order' => $sortOrder,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }
}

if (! function_exists('insertSubcategory')) {
    function insertSubcategory(CategoryId $categoryId, string $name = 'Hair'): SubcategoryId
    {
        $id = SubcategoryId::generate();
        SubcategoryModel::query()->insert([
            'id' => $id->toString(),
            'category_id' => $categoryId->toString(),
            'name' => $name,
            'slug' => strtolower($name).'-'.substr($id->toString(), 0, 8),
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }
}

if (! function_exists('saveTimeSlotService')) {
    function saveTimeSlotService(
        string $name,
        CategoryId $categoryId,
        int $priceCents = 100000,
        ?SubcategoryId $subcategoryId = null,
        string $description = 'desc',
        int $duration = 60,
    ): Service {
        $service = Service::createTimeSlot(
            ServiceId::generate(),
            $name,
            $description,
            Money::fromCents($priceCents, 'RUB'),
            Duration::ofMinutes($duration),
            $categoryId,
            $subcategoryId,
        );
        app(ServiceRepositoryInterface::class)->save($service);

        return $service;
    }
}

if (! function_exists('listServicesHandler')) {
    function listServicesHandler(): ListServicesHandler
    {
        return app(ListServicesHandler::class);
    }
}
