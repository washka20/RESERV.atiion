<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\Query\ListPayoutsByOrganization;

/**
 * Запрос: список выплат организации с пагинацией.
 */
final readonly class ListPayoutsByOrganizationQuery
{
    public function __construct(
        public string $organizationId,
        public int $page = 1,
        public int $perPage = 20,
    ) {}
}
