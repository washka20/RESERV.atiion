<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\Query\GetOrganizationStats;

/**
 * Запрос: статистика организации за последние 30 дней.
 */
final readonly class GetOrganizationStatsQuery
{
    public function __construct(public string $organizationId) {}
}
