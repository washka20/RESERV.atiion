<?php

declare(strict_types=1);

namespace App\Modules\Payment\Domain\Event;

use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Identity\Domain\ValueObject\OrganizationId;
use App\Modules\Payment\Domain\ValueObject\PayoutTransactionId;
use App\Shared\Domain\DomainEvent;
use DateTimeImmutable;

/**
 * Выплата отмечена как выплаченная организации (терминальный статус PAID).
 */
final readonly class PayoutMarkedPaid implements DomainEvent
{
    public function __construct(
        private PayoutTransactionId $id,
        private OrganizationId $organizationId,
        private Money $netAmount,
        private DateTimeImmutable $occurredAt,
    ) {}

    public function id(): PayoutTransactionId
    {
        return $this->id;
    }

    public function organizationId(): OrganizationId
    {
        return $this->organizationId;
    }

    public function netAmount(): Money
    {
        return $this->netAmount;
    }

    public function aggregateId(): string
    {
        return $this->id->toString();
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function eventName(): string
    {
        return 'payout.marked_paid';
    }

    public function payload(): array
    {
        return [
            'payout_transaction_id' => $this->id->toString(),
            'organization_id' => $this->organizationId->toString(),
            'net_amount' => $this->netAmount->amount(),
            'currency' => $this->netAmount->currency(),
            'occurred_at' => $this->occurredAt->format(DATE_ATOM),
        ];
    }

    public static function fromPayload(array $payload): self
    {
        $currency = (string) $payload['currency'];

        return new self(
            new PayoutTransactionId((string) $payload['payout_transaction_id']),
            new OrganizationId((string) $payload['organization_id']),
            Money::fromCents((int) $payload['net_amount'], $currency),
            new DateTimeImmutable((string) $payload['occurred_at']),
        );
    }
}
