<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Query\GetOrganizationBySlug;

/**
 * Query получения организации по slug — публичный endpoint /o/{slug}.
 */
final readonly class GetOrganizationBySlugQuery
{
    public function __construct(
        public string $slug,
    ) {}
}
