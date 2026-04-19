<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Persistence\Repository;

use App\Modules\Identity\Domain\Entity\Organization;
use App\Modules\Identity\Domain\Repository\OrganizationRepositoryInterface;
use App\Modules\Identity\Domain\ValueObject\OrganizationId;
use App\Modules\Identity\Domain\ValueObject\OrganizationSlug;
use App\Modules\Identity\Domain\ValueObject\UserId;
use App\Modules\Identity\Infrastructure\Persistence\Mapper\OrganizationMapper;
use App\Modules\Identity\Infrastructure\Persistence\Model\MembershipModel;
use App\Modules\Identity\Infrastructure\Persistence\Model\OrganizationModel;

/**
 * Eloquent-реализация OrganizationRepository поверх таблицы organizations.
 *
 * Сохранение через updateOrCreate — один и тот же метод save() используется
 * для insert и update (id всегда задаётся в domain factory / reconstitute).
 */
final class EloquentOrganizationRepository implements OrganizationRepositoryInterface
{
    public function save(Organization $organization): void
    {
        $attributes = OrganizationMapper::toArray($organization);

        OrganizationModel::query()->updateOrCreate(
            ['id' => $attributes['id']],
            $attributes,
        );
    }

    public function findById(OrganizationId $id): ?Organization
    {
        $model = OrganizationModel::query()->find($id->toString());

        return $model !== null ? OrganizationMapper::toDomain($model) : null;
    }

    public function findBySlug(OrganizationSlug $slug): ?Organization
    {
        $model = OrganizationModel::query()->where('slug', $slug->toString())->first();

        return $model !== null ? OrganizationMapper::toDomain($model) : null;
    }

    public function existsBySlug(OrganizationSlug $slug): bool
    {
        return OrganizationModel::query()->where('slug', $slug->toString())->exists();
    }

    public function findByUserId(UserId $userId): array
    {
        $models = OrganizationModel::query()
            ->whereIn(
                'id',
                MembershipModel::query()
                    ->where('user_id', $userId->toString())
                    ->select('organization_id'),
            )
            ->orderBy('created_at')
            ->get();

        $result = [];
        foreach ($models as $model) {
            /** @var OrganizationModel $model */
            $result[] = OrganizationMapper::toDomain($model);
        }

        return $result;
    }
}
