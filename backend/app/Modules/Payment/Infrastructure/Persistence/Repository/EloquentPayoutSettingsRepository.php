<?php

declare(strict_types=1);

namespace App\Modules\Payment\Infrastructure\Persistence\Repository;

use App\Modules\Identity\Domain\ValueObject\OrganizationId;
use App\Modules\Payment\Domain\Entity\PayoutSettings;
use App\Modules\Payment\Domain\Repository\PayoutSettingsRepositoryInterface;
use App\Modules\Payment\Infrastructure\Persistence\Mapper\PayoutSettingsMapper;
use App\Modules\Payment\Infrastructure\Persistence\Model\PayoutSettingsModel;

/**
 * Eloquent реализация PayoutSettingsRepositoryInterface.
 *
 * save() — upsert по organization_id, что гарантирует unique constraint на уровне таблицы.
 */
final class EloquentPayoutSettingsRepository implements PayoutSettingsRepositoryInterface
{
    public function save(PayoutSettings $settings): void
    {
        PayoutSettingsModel::query()->updateOrCreate(
            ['organization_id' => $settings->organizationId()->toString()],
            PayoutSettingsMapper::toArray($settings),
        );
    }

    public function findByOrganizationId(OrganizationId $id): ?PayoutSettings
    {
        $model = PayoutSettingsModel::query()
            ->where('organization_id', $id->toString())
            ->first();

        return $model !== null ? PayoutSettingsMapper::toDomain($model) : null;
    }
}
