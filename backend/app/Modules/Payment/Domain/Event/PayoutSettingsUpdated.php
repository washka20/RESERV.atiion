<?php

declare(strict_types=1);

namespace App\Modules\Payment\Domain\Event;

use App\Modules\Identity\Domain\ValueObject\OrganizationId;
use App\Shared\Domain\DomainEvent;
use DateTimeImmutable;

/**
 * Payout settings организации обновлены.
 *
 * Payload содержит только OrganizationId + occurredAt — чувствительные банковские реквизиты
 * НЕ публикуются в outbox / event bus, чтобы не утекли через логи и подписчиков.
 */
final readonly class PayoutSettingsUpdated implements DomainEvent
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
        return 'payout.settings_updated';
    }

    public function payload(): array
    {
        return [
            'organization_id' => $this->organizationId->toString(),
            'occurred_at' => $this->occurredAt->format(DATE_ATOM),
        ];
    }

    public static function fromPayload(array $payload): self
    {
        return new self(
            new OrganizationId((string) $payload['organization_id']),
            new DateTimeImmutable((string) $payload['occurred_at']),
        );
    }
}
