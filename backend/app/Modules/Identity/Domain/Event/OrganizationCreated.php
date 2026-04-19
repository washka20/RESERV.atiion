<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Event;

use App\Modules\Identity\Domain\ValueObject\OrganizationId;
use App\Modules\Identity\Domain\ValueObject\OrganizationSlug;
use App\Modules\Identity\Domain\ValueObject\OrganizationType;
use App\Shared\Domain\DomainEvent;
use DateTimeImmutable;

/**
 * Organization создана. Slug сгенерирован (или передан явно).
 * Owner Membership создаётся в том же transaction, но как отдельный event.
 */
final readonly class OrganizationCreated implements DomainEvent
{
    public function __construct(
        private OrganizationId $organizationId,
        private OrganizationSlug $slug,
        private OrganizationType $type,
        private DateTimeImmutable $occurredAt,
    ) {}

    public function organizationId(): OrganizationId
    {
        return $this->organizationId;
    }

    public function slug(): OrganizationSlug
    {
        return $this->slug;
    }

    public function type(): OrganizationType
    {
        return $this->type;
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
        return 'identity.organization.created';
    }

    public function payload(): array
    {
        return [
            'organization_id' => $this->organizationId->toString(),
            'slug' => $this->slug->toString(),
            'type' => $this->type->value,
            'occurred_at' => $this->occurredAt->format(DATE_ATOM),
        ];
    }

    public static function fromPayload(array $payload): self
    {
        return new self(
            new OrganizationId((string) $payload['organization_id']),
            new OrganizationSlug((string) $payload['slug']),
            OrganizationType::from((string) $payload['type']),
            new DateTimeImmutable((string) $payload['occurred_at']),
        );
    }
}
