<?php

declare(strict_types=1);

namespace App\Modules\Payment\Domain\Event;

use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Payment\Domain\ValueObject\PaymentId;
use App\Shared\Domain\DomainEvent;
use DateTimeImmutable;

/**
 * Платёж получен (статус PAID). Содержит рассчитанные platform fee и net.
 */
final readonly class PaymentReceived implements DomainEvent
{
    public function __construct(
        private PaymentId $id,
        private BookingId $bookingId,
        private Money $gross,
        private Money $platformFee,
        private Money $net,
        private string $providerRef,
        private DateTimeImmutable $occurredAt,
    ) {}

    public function id(): PaymentId
    {
        return $this->id;
    }

    public function bookingId(): BookingId
    {
        return $this->bookingId;
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

    public function providerRef(): string
    {
        return $this->providerRef;
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
        return 'payment.received';
    }

    public function payload(): array
    {
        return [
            'payment_id' => $this->id->toString(),
            'booking_id' => $this->bookingId->toString(),
            'gross_amount' => $this->gross->amount(),
            'platform_fee_amount' => $this->platformFee->amount(),
            'net_amount' => $this->net->amount(),
            'currency' => $this->gross->currency(),
            'provider_ref' => $this->providerRef,
            'occurred_at' => $this->occurredAt->format(DATE_ATOM),
        ];
    }

    public static function fromPayload(array $payload): self
    {
        $currency = (string) $payload['currency'];

        return new self(
            new PaymentId((string) $payload['payment_id']),
            new BookingId((string) $payload['booking_id']),
            Money::fromCents((int) $payload['gross_amount'], $currency),
            Money::fromCents((int) $payload['platform_fee_amount'], $currency),
            Money::fromCents((int) $payload['net_amount'], $currency),
            (string) $payload['provider_ref'],
            new DateTimeImmutable((string) $payload['occurred_at']),
        );
    }
}
