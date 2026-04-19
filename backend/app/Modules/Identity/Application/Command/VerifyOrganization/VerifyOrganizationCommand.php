<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Command\VerifyOrganization;

/**
 * Команда верификации организации (KYC). Admin-only.
 *
 * Admin-gate реализован на route/middleware уровне — handler просто выполняет
 * verify() на aggregate. Идемпотентно: повторный verify не эмитит event.
 */
final readonly class VerifyOrganizationCommand
{
    public function __construct(
        public string $organizationId,
    ) {}
}
