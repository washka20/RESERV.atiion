<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Persistence\Mapper;

use App\Modules\Identity\Domain\Entity\Membership;
use App\Modules\Identity\Domain\ValueObject\MembershipId;
use App\Modules\Identity\Domain\ValueObject\MembershipRole;
use App\Modules\Identity\Domain\ValueObject\OrganizationId;
use App\Modules\Identity\Domain\ValueObject\UserId;
use App\Modules\Identity\Infrastructure\Persistence\Model\MembershipModel;
use DateTimeImmutable;

/**
 * Маппер Membership ↔ MembershipModel.
 */
final class MembershipMapper
{
    /**
     * Восстанавливает Membership из Eloquent-модели без эмита domain events.
     */
    public static function toDomain(MembershipModel $model): Membership
    {
        return Membership::reconstitute(
            id: new MembershipId($model->id),
            userId: new UserId($model->user_id),
            organizationId: new OrganizationId($model->organization_id),
            role: MembershipRole::from($model->role),
            invitedBy: $model->invited_by !== null ? new UserId($model->invited_by) : null,
            acceptedAt: self::toDateTime($model->accepted_at),
            createdAt: self::toDateTime($model->created_at) ?? new DateTimeImmutable,
            updatedAt: self::toDateTime($model->updated_at) ?? new DateTimeImmutable,
        );
    }

    /**
     * Формирует массив для Eloquent upsert (snake_case).
     *
     * @return array<string, mixed>
     */
    public static function toArray(Membership $membership): array
    {
        return [
            'id' => $membership->id->toString(),
            'user_id' => $membership->userId->toString(),
            'organization_id' => $membership->organizationId->toString(),
            'role' => $membership->role()->value,
            'invited_by' => $membership->invitedBy?->toString(),
            'accepted_at' => $membership->acceptedAt()?->format(DATE_ATOM),
            'created_at' => $membership->createdAt->format(DATE_ATOM),
            'updated_at' => $membership->updatedAt()->format(DATE_ATOM),
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
