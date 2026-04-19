<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Event;

use App\Modules\Identity\Domain\ValueObject\MembershipId;
use App\Modules\Identity\Domain\ValueObject\OrganizationId;
use App\Modules\Identity\Domain\ValueObject\UserId;
use App\Shared\Domain\DomainEvent;
use DateTimeImmutable;

/**
 * Membership отозван — user больше не член organization.
 * Последнего owner'а нельзя revoke (LastOwnerCannotBeRevokedException).
 */
final readonly class MembershipRevoked implements DomainEvent
{
    public function __construct(
        private MembershipId $membershipId,
        private UserId $userId,
        private OrganizationId $organizationId,
        private DateTimeImmutable $occurredAt,
    ) {}

    public function membershipId(): MembershipId
    {
        return $this->membershipId;
    }

    public function userId(): UserId
    {
        return $this->userId;
    }

    public function organizationId(): OrganizationId
    {
        return $this->organizationId;
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
        return 'identity.membership.revoked';
    }

    public function payload(): array
    {
        return [
            'membership_id' => $this->membershipId->toString(),
            'user_id' => $this->userId->toString(),
            'organization_id' => $this->organizationId->toString(),
            'occurred_at' => $this->occurredAt->format(DATE_ATOM),
        ];
    }

    public static function fromPayload(array $payload): self
    {
        return new self(
            new MembershipId((string) $payload['membership_id']),
            new UserId((string) $payload['user_id']),
            new OrganizationId((string) $payload['organization_id']),
            new DateTimeImmutable((string) $payload['occurred_at']),
        );
    }
}
