<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Command\InviteMember;

/**
 * Команда приглашения member'а в организацию. Роль owner недопустима —
 * owner создаётся только через CreateOrganization или promote из admin.
 */
final readonly class InviteMemberCommand
{
    public function __construct(
        public string $organizationSlug,
        public string $actorUserId,
        public string $inviteeEmail,
        public string $role,
    ) {}
}
