<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Persistence\Mapper;

use App\Modules\Identity\Domain\Entity\Organization;
use App\Modules\Identity\Domain\ValueObject\CancellationPolicy;
use App\Modules\Identity\Domain\ValueObject\OrganizationId;
use App\Modules\Identity\Domain\ValueObject\OrganizationSlug;
use App\Modules\Identity\Domain\ValueObject\OrganizationType;
use App\Modules\Identity\Infrastructure\Persistence\Model\OrganizationModel;
use DateTimeImmutable;

/**
 * Маппер Organization ↔ OrganizationModel.
 *
 * toDomain восстанавливает entity через Organization::reconstitute (без events).
 * toArray формирует snake_case payload для insert/update запроса.
 */
final class OrganizationMapper
{
    /**
     * Восстанавливает Organization из Eloquent-модели без эмита domain events.
     */
    public static function toDomain(OrganizationModel $model): Organization
    {
        /** @var array<string, string> $name */
        $name = $model->name ?? [];
        /** @var array<string, string> $description */
        $description = $model->description ?? [];

        return Organization::reconstitute(
            id: new OrganizationId($model->id),
            slug: new OrganizationSlug($model->slug),
            name: $name,
            description: $description,
            type: OrganizationType::from($model->type),
            logoUrl: $model->logo_url,
            city: $model->city,
            district: $model->district,
            phone: $model->phone,
            email: $model->email,
            verified: (bool) $model->verified,
            cancellationPolicy: CancellationPolicy::from($model->cancellation_policy),
            rating: (float) $model->rating,
            reviewsCount: (int) $model->reviews_count,
            archivedAt: self::toDateTime($model->archived_at),
            createdAt: self::toDateTime($model->created_at) ?? new DateTimeImmutable,
            updatedAt: self::toDateTime($model->updated_at) ?? new DateTimeImmutable,
        );
    }

    /**
     * Формирует массив для Eloquent-запроса (insert/update). Timestamp-поля
     * форматируются в ISO-8601 — совместимы с timestampTz колонками Postgres.
     *
     * @return array<string, mixed>
     */
    public static function toArray(Organization $organization): array
    {
        return [
            'id' => $organization->id->toString(),
            'slug' => $organization->slug->toString(),
            'name' => $organization->nameTranslations(),
            'description' => $organization->descriptionTranslations(),
            'type' => $organization->type->value,
            'logo_url' => $organization->logoUrl(),
            'city' => $organization->city(),
            'district' => $organization->district(),
            'phone' => $organization->phone(),
            'email' => $organization->email(),
            'verified' => $organization->isVerified(),
            'cancellation_policy' => $organization->cancellationPolicy()->value,
            'rating' => $organization->rating(),
            'reviews_count' => $organization->reviewsCount(),
            'archived_at' => $organization->archivedAt()?->format(DATE_ATOM),
            'created_at' => $organization->createdAt->format(DATE_ATOM),
            'updated_at' => $organization->updatedAt()->format(DATE_ATOM),
        ];
    }

    private static function toDateTime(mixed $value): ?DateTimeImmutable
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof DateTimeImmutable) {
            return $value;
        }

        if (is_object($value) && method_exists($value, 'toDateTimeImmutable')) {
            return $value->toDateTimeImmutable();
        }

        return new DateTimeImmutable((string) $value);
    }
}
