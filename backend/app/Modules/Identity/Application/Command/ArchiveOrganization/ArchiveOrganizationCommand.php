<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Command\ArchiveOrganization;

/**
 * Команда архивирования организации. Доступно только owner'ам
 * (permission organization.archive).
 */
final readonly class ArchiveOrganizationCommand
{
    public function __construct(
        public string $organizationSlug,
        public string $actorUserId,
    ) {}
}
