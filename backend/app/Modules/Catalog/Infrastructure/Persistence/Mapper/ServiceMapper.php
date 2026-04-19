<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Persistence\Mapper;

use App\Modules\Catalog\Domain\Entity\Service;
use App\Modules\Catalog\Domain\ValueObject\CategoryId;
use App\Modules\Catalog\Domain\ValueObject\Duration;
use App\Modules\Catalog\Domain\ValueObject\ImagePath;
use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use App\Modules\Catalog\Domain\ValueObject\ServiceType;
use App\Modules\Catalog\Domain\ValueObject\SubcategoryId;
use App\Modules\Catalog\Infrastructure\Persistence\Model\ServiceImageModel;
use App\Modules\Catalog\Infrastructure\Persistence\Model\ServiceModel;
use App\Modules\Identity\Domain\ValueObject\OrganizationId;

/**
 * Маппер Service ↔ ServiceModel.
 *
 * toDomain читает eager-loaded relation `images` и преобразует в list<ImagePath>.
 * toPersistence возвращает массив для updateOrInsert — images сохраняются отдельно репозиторием.
 */
final class ServiceMapper
{
    public static function toDomain(ServiceModel $model): Service
    {
        /** @var list<ImagePath> $images */
        $images = [];
        foreach ($model->images as $imageModel) {
            /** @var ServiceImageModel $imageModel */
            $images[] = ImagePath::fromString($imageModel->path);
        }

        $type = ServiceType::from((string) $model->type);
        $duration = $model->duration_minutes !== null
            ? Duration::ofMinutes((int) $model->duration_minutes)
            : null;
        $subcategoryId = $model->subcategory_id !== null
            ? new SubcategoryId($model->subcategory_id)
            : null;
        $organizationId = $model->organization_id !== null
            ? new OrganizationId((string) $model->organization_id)
            : null;

        return Service::restore(
            new ServiceId($model->id),
            (string) $model->name,
            (string) $model->description,
            Money::fromCents((int) $model->price_amount, (string) $model->price_currency),
            $type,
            $duration,
            $model->total_quantity !== null ? (int) $model->total_quantity : null,
            new CategoryId($model->category_id),
            $subcategoryId,
            (bool) $model->is_active,
            $images,
            $model->created_at->toDateTimeImmutable(),
            $model->updated_at->toDateTimeImmutable(),
            $organizationId,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function toPersistence(Service $service): array
    {
        return [
            'id' => $service->id()->toString(),
            'name' => $service->name(),
            'description' => $service->description(),
            'price_amount' => $service->price()->amount(),
            'price_currency' => $service->price()->currency(),
            'type' => $service->type()->value,
            'duration_minutes' => $service->duration()?->minutes(),
            'total_quantity' => $service->totalQuantity(),
            'category_id' => $service->categoryId()->toString(),
            'subcategory_id' => $service->subcategoryId()?->toString(),
            'organization_id' => $service->organizationId()?->toString(),
            'is_active' => $service->isActive(),
            'created_at' => $service->createdAt(),
            'updated_at' => $service->updatedAt(),
        ];
    }
}
