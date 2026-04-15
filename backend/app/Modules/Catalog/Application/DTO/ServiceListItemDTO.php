<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\DTO;

/**
 * Компактное представление услуги для list-вью (каталог, результаты поиска).
 */
final readonly class ServiceListItemDTO
{
    public function __construct(
        public string $id,
        public string $name,
        public int $priceAmount,
        public string $priceCurrency,
        public string $type,
        public string $categoryName,
        public ?string $primaryImage,
        public bool $isActive,
    ) {}
}
