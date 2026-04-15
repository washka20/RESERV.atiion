<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Query\ListServices;

/**
 * Запрос списка услуг с фильтрами и пагинацией (read-side CQRS).
 */
final readonly class ListServicesQuery
{
    public function __construct(
        public ?string $categoryId = null,
        public ?string $subcategoryId = null,
        public ?string $type = null,
        public ?bool $isActive = true,
        public ?string $search = null,
        public ?int $minPrice = null,
        public ?int $maxPrice = null,
        public int $page = 1,
        public int $perPage = 20,
    ) {}
}
