<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Query\GetService;

use App\Modules\Catalog\Application\DTO\ServiceDTO;
use App\Modules\Catalog\Domain\Exception\ServiceNotFoundException;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Возвращает полное представление услуги по идентификатору.
 *
 * @throws ServiceNotFoundException если услуга не найдена
 */
final readonly class GetServiceHandler
{
    public function handle(GetServiceQuery $query): ServiceDTO
    {
        $row = DB::table('services as s')
            ->leftJoin('categories as c', 's.category_id', '=', 'c.id')
            ->leftJoin('subcategories as sub', 's.subcategory_id', '=', 'sub.id')
            ->select([
                's.id',
                's.name',
                's.description',
                's.price_amount',
                's.price_currency',
                's.type',
                's.duration_minutes',
                's.total_quantity',
                's.is_active',
                's.category_id',
                's.subcategory_id',
                's.created_at',
                's.updated_at',
                'c.name as category_name',
                'sub.name as subcategory_name',
            ])
            ->where('s.id', $query->serviceId)
            ->first();

        if ($row === null) {
            throw ServiceNotFoundException::byId(new ServiceId($query->serviceId));
        }

        $images = DB::table('service_images')
            ->where('service_id', $query->serviceId)
            ->orderBy('sort_order')
            ->pluck('path')
            ->map(static fn ($p) => (string) $p)
            ->all();

        return new ServiceDTO(
            id: (string) $row->id,
            name: (string) $row->name,
            description: (string) $row->description,
            priceAmount: (int) $row->price_amount,
            priceCurrency: (string) $row->price_currency,
            type: (string) $row->type,
            durationMinutes: $row->duration_minutes !== null ? (int) $row->duration_minutes : null,
            totalQuantity: $row->total_quantity !== null ? (int) $row->total_quantity : null,
            categoryId: (string) $row->category_id,
            categoryName: (string) $row->category_name,
            subcategoryId: $row->subcategory_id !== null ? (string) $row->subcategory_id : null,
            subcategoryName: $row->subcategory_name !== null ? (string) $row->subcategory_name : null,
            isActive: (bool) $row->is_active,
            images: array_values($images),
            createdAt: Carbon::parse((string) $row->created_at)->toIso8601String(),
            updatedAt: Carbon::parse((string) $row->updated_at)->toIso8601String(),
        );
    }
}
