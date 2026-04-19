<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\Query\GetPayoutSettings;

use App\Modules\Identity\Domain\ValueObject\OrganizationId;
use App\Modules\Payment\Application\DTO\PayoutSettingsDTO;
use App\Modules\Payment\Domain\Repository\PayoutSettingsRepositoryInterface;

/**
 * Handler GetPayoutSettingsQuery.
 *
 * Возвращает DTO или null (если настройки ещё не создавались). API-слой транслирует
 * null в 404 или пустой ответ — выбор за Controller.
 */
final readonly class GetPayoutSettingsHandler
{
    public function __construct(private PayoutSettingsRepositoryInterface $repo) {}

    public function handle(GetPayoutSettingsQuery $query): ?PayoutSettingsDTO
    {
        $settings = $this->repo->findByOrganizationId(new OrganizationId($query->organizationId));

        return $settings !== null ? PayoutSettingsDTO::fromEntity($settings) : null;
    }
}
