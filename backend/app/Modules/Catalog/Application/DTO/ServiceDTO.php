<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\DTO;

/**
 * Полное представление услуги для read-моделей (detail view, admin, API).
 */
final readonly class ServiceDTO
{
    /**
     * @param  list<string>  $images
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $description,
        public int $priceAmount,
        public string $priceCurrency,
        public string $type,
        public ?int $durationMinutes,
        public ?int $totalQuantity,
        public string $categoryId,
        public string $categoryName,
        public ?string $subcategoryId,
        public ?string $subcategoryName,
        public bool $isActive,
        public array $images,
        public string $createdAt,
        public string $updatedAt,
    ) {}
}
