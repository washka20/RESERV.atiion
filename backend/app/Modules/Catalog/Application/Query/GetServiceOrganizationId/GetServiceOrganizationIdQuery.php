<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Query\GetServiceOrganizationId;

/**
 * Query: получить organization_id услуги по её идентификатору.
 *
 * Используется listener'ами других BC (напр. Payment) для резолва владельца
 * организации по service_id из domain event.
 */
final readonly class GetServiceOrganizationIdQuery
{
    public function __construct(
        public string $serviceId,
    ) {}
}
