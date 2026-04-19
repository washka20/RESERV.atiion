<?php

declare(strict_types=1);

namespace App\Modules\Payment\Domain\Repository;

use App\Modules\Identity\Domain\ValueObject\OrganizationId;
use App\Modules\Payment\Domain\Entity\PayoutSettings;

/**
 * Репозиторий {@see PayoutSettings} — один агрегат на organization.
 */
interface PayoutSettingsRepositoryInterface
{
    public function save(PayoutSettings $settings): void;

    public function findByOrganizationId(OrganizationId $id): ?PayoutSettings;
}
