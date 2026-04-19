<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Query\GetServiceOrganizationId;

use Illuminate\Database\ConnectionInterface;

/**
 * Handler GetServiceOrganizationIdQuery.
 *
 * Read-side CQRS: обходит Eloquent, читает через DB connection.
 * Возвращает null если услуга не найдена или organization_id не задан.
 */
final readonly class GetServiceOrganizationIdHandler
{
    public function __construct(private ConnectionInterface $db) {}

    public function handle(GetServiceOrganizationIdQuery $query): ?string
    {
        $value = $this->db->table('services')
            ->where('id', $query->serviceId)
            ->value('organization_id');

        return $value !== null ? (string) $value : null;
    }
}
