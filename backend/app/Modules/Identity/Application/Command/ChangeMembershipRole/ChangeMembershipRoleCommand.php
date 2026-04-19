<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Command\ChangeMembershipRole;

/**
 * Команда смены роли membership. Enforcement last-owner invariant в handler.
 */
final readonly class ChangeMembershipRoleCommand
{
    public function __construct(
        public string $organizationSlug,
        public string $actorUserId,
        public string $targetMembershipId,
        public string $newRole,
    ) {}
}
