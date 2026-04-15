<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Interface\Api\Resource;

use App\Modules\Catalog\Application\DTO\ServiceDTO;

/**
 * Сериализует ServiceDTO для публичного detail-эндпоинта каталога.
 */
final class ServiceResource
{
    /**
     * @return array<string, mixed>
     */
    public static function fromDTO(ServiceDTO $dto): array
    {
        return [
            'id' => $dto->id,
            'name' => $dto->name,
            'description' => $dto->description,
            'price_amount' => $dto->priceAmount,
            'price_currency' => $dto->priceCurrency,
            'type' => $dto->type,
            'duration_minutes' => $dto->durationMinutes,
            'total_quantity' => $dto->totalQuantity,
            'category_id' => $dto->categoryId,
            'category_name' => $dto->categoryName,
            'subcategory_id' => $dto->subcategoryId,
            'subcategory_name' => $dto->subcategoryName,
            'is_active' => $dto->isActive,
            'images' => $dto->images,
            'created_at' => $dto->createdAt,
            'updated_at' => $dto->updatedAt,
        ];
    }
}
