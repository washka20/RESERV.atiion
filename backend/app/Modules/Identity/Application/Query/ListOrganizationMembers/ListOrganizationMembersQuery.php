<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Query\ListOrganizationMembers;

/**
 * Query списка member'ов организации. Требует membership actor'а
 * с permission team.view.
 */
final readonly class ListOrganizationMembersQuery
{
    public function __construct(
        public string $organizationSlug,
        public string $actorUserId,
    ) {}
}
