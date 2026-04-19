<?php

declare(strict_types=1);

namespace App\Modules\Payment\Domain\Event;

use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Identity\Domain\ValueObject\OrganizationId;
use App\Modules\Payment\Domain\ValueObject\PaymentId;
use App\Modules\Payment\Domain\ValueObject\PayoutTransactionId;
use App\Shared\Domain\DomainEvent;
use DateTimeImmutable;

/**
 * Выплата создана в статусе PENDING. Фиксирует gross = platformFee + net.
 */
final readonly class PayoutTransactionCreated implements DomainEvent
{
    public function __construct(
        private PayoutTransactionId $id,
        private BookingId $bookingId,
        private OrganizationId $organizationId,
        private PaymentId $paymentId,
        private Money $gross,
        private Money $platformFee,
        private Money $net,
        private DateTimeImmutable $occurredAt,
    ) {}

    public function id(): PayoutTransactionId
    {
        return $this->id;
    }

    public function bookingId(): BookingId
    {
        return $this->bookingId;
    }

    public function organizationId(): OrganizationId
    {
        return $this->organizationId;
    }

    public function paymentId(): PaymentId
    {
        return $this->paymentId;
    }

    public function gross(): Money
    {
        return $this->gross;
    }

    public function platformFee(): Money
    {
        return $this->platformFee;
    }

    public function net(): Money
    {
        return $this->net;
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
        return 'payout.transaction_created';
    }

    public function payload(): array
    {
        return [
            'payout_transaction_id' => $this->id->toString(),
            'booking_id' => $this->bookingId->toString(),
            'organization_id' => $this->organizationId->toString(),
            'payment_id' => $this->paymentId->toString(),
            'gross_amount' => $this->gross->amount(),
            'platform_fee_amount' => $this->platformFee->amount(),
            'net_amount' => $this->net->amount(),
            'currency' => $this->gross->currency(),
            'occurred_at' => $this->occurredAt->format(DATE_ATOM),
        ];
    }

    public static function fromPayload(array $payload): self
    {
        $currency = (string) $payload['currency'];

        return new self(
            new PayoutTransactionId((string) $payload['payout_transaction_id']),
            new BookingId((string) $payload['booking_id']),
            new OrganizationId((string) $payload['organization_id']),
            new PaymentId((string) $payload['payment_id']),
            Money::fromCents((int) $payload['gross_amount'], $currency),
            Money::fromCents((int) $payload['platform_fee_amount'], $currency),
            Money::fromCents((int) $payload['net_amount'], $currency),
            new DateTimeImmutable((string) $payload['occurred_at']),
        );
    }
}
