<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Command\RevokeMembership;

/**
 * Команда отзыва membership. Защита last-owner enforcement'ится handler'ом.
 */
final readonly class RevokeMembershipCommand
{
    public function __construct(
        public string $organizationSlug,
        public string $actorUserId,
        public string $targetMembershipId,
    ) {}
}
