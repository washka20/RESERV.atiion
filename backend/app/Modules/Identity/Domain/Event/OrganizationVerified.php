<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Event;

use App\Modules\Identity\Domain\ValueObject\OrganizationId;
use App\Shared\Domain\DomainEvent;
use DateTimeImmutable;

/**
 * Organization прошла KYC — получен verified badge.
 * Эмитит admin через Filament (Plan 11 добавит real KYC review UI).
 */
final readonly class OrganizationVerified implements DomainEvent
{
    public function __construct(
        private OrganizationId $organizationId,
        private DateTimeImmutable $occurredAt,
    ) {}

    public function organizationId(): OrganizationId
    {
        return $this->organizationId;
    }

    public function aggregateId(): string
    {
        return $this->organizationId->toString();
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function eventName(): string
    {
        return 'identity.organization.verified';
    }

    public function payload(): array
    {
        return [
            'organization_id' => $this->organizationId->toString(),
            'occurred_at' => $this->occurredAt->format(DATE_ATOM),
        ];
    }
}
