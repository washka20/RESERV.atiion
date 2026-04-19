<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\DTO;

/**
 * Проекция member'а организации с embedded user info. Используется
 * admin UI / staff management view.
 */
final readonly class MemberListItemDTO
{
    public function __construct(
        public string $membershipId,
        public string $userId,
        public string $userEmail,
        public string $userFirstName,
        public string $userLastName,
        public string $role,
        public ?string $acceptedAt,
        public string $joinedAt,
    ) {}
}
