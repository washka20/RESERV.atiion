<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\DTO;

/**
 * Проекция Membership + базовая инфо про Organization. Используется для
 * JWT claims (login/refresh) — чтобы frontend мог переключать active
 * organization без дополнительного round-trip.
 */
final readonly class MembershipWithOrgDTO
{
    public function __construct(
        public string $membershipId,
        public string $organizationId,
        public string $organizationSlug,
        public string $role,
    ) {}
}
