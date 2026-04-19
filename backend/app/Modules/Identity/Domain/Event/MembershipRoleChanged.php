<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Event;

use App\Modules\Identity\Domain\ValueObject\MembershipId;
use App\Modules\Identity\Domain\ValueObject\MembershipRole;
use App\Shared\Domain\DomainEvent;
use DateTimeImmutable;

/**
 * Роль Membership изменена (promote/demote).
 * Идентичный newRole === oldRole — idempotent no-op, event не эмитится.
 */
final readonly class MembershipRoleChanged implements DomainEvent
{
    public function __construct(
        private MembershipId $membershipId,
        private MembershipRole $oldRole,
        private MembershipRole $newRole,
        private DateTimeImmutable $occurredAt,
    ) {}

    public function membershipId(): MembershipId
    {
        return $this->membershipId;
    }

    public function oldRole(): MembershipRole
    {
        return $this->oldRole;
    }

    public function newRole(): MembershipRole
    {
        return $this->newRole;
    }

    public function aggregateId(): string
    {
        return $this->membershipId->toString();
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function eventName(): string
    {
        return 'identity.membership.role_changed';
    }

    public function payload(): array
    {
        return [
            'membership_id' => $this->membershipId->toString(),
            'old_role' => $this->oldRole->value,
            'new_role' => $this->newRole->value,
            'occurred_at' => $this->occurredAt->format(DATE_ATOM),
        ];
    }

    public static function fromPayload(array $payload): self
    {
        return new self(
            new MembershipId((string) $payload['membership_id']),
            MembershipRole::from((string) $payload['old_role']),
            MembershipRole::from((string) $payload['new_role']),
            new DateTimeImmutable((string) $payload['occurred_at']),
        );
    }
}
