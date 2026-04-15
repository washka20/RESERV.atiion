<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Interface\Api\Resource;

use App\Modules\Catalog\Application\DTO\ServiceListItemDTO;

/**
 * Сериализует ServiceListItemDTO для публичного list-эндпоинта каталога.
 */
final class ServiceListItemResource
{
    /**
     * @return array<string, mixed>
     */
    public static function fromDTO(ServiceListItemDTO $dto): array
    {
        return [
            'id' => $dto->id,
            'name' => $dto->name,
            'price_amount' => $dto->priceAmount,
            'price_currency' => $dto->priceCurrency,
            'type' => $dto->type,
            'category_name' => $dto->categoryName,
            'primary_image' => $dto->primaryImage,
            'is_active' => $dto->isActive,
        ];
    }
}
