<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Query\GetOrganization;

/**
 * Query получения организации по id.
 */
final readonly class GetOrganizationQuery
{
    public function __construct(
        public string $organizationId,
    ) {}
}
