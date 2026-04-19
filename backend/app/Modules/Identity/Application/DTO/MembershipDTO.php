<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\DTO;

use App\Modules\Identity\Domain\Entity\Membership;

/**
 * DTO проекции Membership для Application/API слоёв.
 */
final readonly class MembershipDTO
{
    public function __construct(
        public string $id,
        public string $userId,
        public string $organizationId,
        public string $role,
        public ?string $invitedBy,
        public ?string $acceptedAt,
        public string $createdAt,
    ) {}

    public static function fromEntity(Membership $m): self
    {
        return new self(
            id: $m->id->toString(),
            userId: $m->userId->toString(),
            organizationId: $m->organizationId->toString(),
            role: $m->role()->value,
            invitedBy: $m->invitedBy?->toString(),
            acceptedAt: $m->acceptedAt()?->format(DATE_ATOM),
            createdAt: $m->createdAt->format(DATE_ATOM),
        );
    }
}
