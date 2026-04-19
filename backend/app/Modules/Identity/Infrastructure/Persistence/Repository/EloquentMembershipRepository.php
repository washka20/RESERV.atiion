<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Persistence\Repository;

use App\Modules\Identity\Domain\Entity\Membership;
use App\Modules\Identity\Domain\Repository\MembershipRepositoryInterface;
use App\Modules\Identity\Domain\ValueObject\MembershipId;
use App\Modules\Identity\Domain\ValueObject\MembershipRole;
use App\Modules\Identity\Domain\ValueObject\OrganizationId;
use App\Modules\Identity\Domain\ValueObject\UserId;
use App\Modules\Identity\Infrastructure\Persistence\Mapper\MembershipMapper;
use App\Modules\Identity\Infrastructure\Persistence\Model\MembershipModel;

/**
 * Eloquent-реализация MembershipRepository.
 *
 * Уникальность пары (user_id, organization_id) обеспечивается индексом БД —
 * при дубле save() бросит QueryException (PG 23505), который application слой
 * конвертирует в MembershipAlreadyExistsException.
 */
final class EloquentMembershipRepository implements MembershipRepositoryInterface
{
    public function save(Membership $membership): void
    {
        $attributes = MembershipMapper::toArray($membership);

        MembershipModel::query()->updateOrCreate(
            ['id' => $attributes['id']],
            $attributes,
        );
    }

    public function findById(MembershipId $id): ?Membership
    {
        $model = MembershipModel::query()->find($id->toString());

        return $model !== null ? MembershipMapper::toDomain($model) : null;
    }

    public function findByPair(UserId $userId, OrganizationId $organizationId): ?Membership
    {
        $model = MembershipModel::query()
            ->where('user_id', $userId->toString())
            ->where('organization_id', $organizationId->toString())
            ->first();

        return $model !== null ? MembershipMapper::toDomain($model) : null;
    }

    public function findByUserId(UserId $userId): array
    {
        $models = MembershipModel::query()
            ->where('user_id', $userId->toString())
            ->orderBy('created_at')
            ->get();

        $result = [];
        foreach ($models as $model) {
            /** @var MembershipModel $model */
            $result[] = MembershipMapper::toDomain($model);
        }

        return $result;
    }

    public function findByOrganizationId(OrganizationId $organizationId): array
    {
        $models = MembershipModel::query()
            ->where('organization_id', $organizationId->toString())
            ->orderBy('created_at')
            ->get();

        $result = [];
        foreach ($models as $model) {
            /** @var MembershipModel $model */
            $result[] = MembershipMapper::toDomain($model);
        }

        return $result;
    }

    public function countOwnersInOrganization(OrganizationId $organizationId): int
    {
        return MembershipModel::query()
            ->where('organization_id', $organizationId->toString())
            ->where('role', MembershipRole::OWNER->value)
            ->count();
    }

    public function delete(MembershipId $id): void
    {
        MembershipModel::query()->where('id', $id->toString())->delete();
    }
}
