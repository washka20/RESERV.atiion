<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Command\AdminArchiveOrganization;

/**
 * Platform-level админ-архивация организации. Используется Filament panel'ом,
 * где actor — platform admin/manager, а не organization owner.
 *
 * Отличается от ArchiveOrganizationCommand тем, что НЕ требует membership —
 * авторизация выполняется на уровне Filament (canViewAny/canArchive gate).
 * Идентификация организации — по UUID (админ видит полный список, включая slug
 * lookups — но uuid стабильнее при миграциях).
 */
final readonly class AdminArchiveOrganizationCommand
{
    public function __construct(
        public string $organizationId,
    ) {}
}
