<?php

declare(strict_types=1);

namespace App\Modules\Payment\Domain\Event;

use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Payment\Domain\ValueObject\PaymentId;
use App\Modules\Payment\Domain\ValueObject\PaymentMethod;
use App\Modules\Payment\Domain\ValueObject\Percentage;
use App\Shared\Domain\DomainEvent;
use DateTimeImmutable;

/**
 * Платёж инициирован (статус PENDING).
 */
final readonly class PaymentInitiated implements DomainEvent
{
    public function __construct(
        private PaymentId $id,
        private BookingId $bookingId,
        private Money $gross,
        private PaymentMethod $method,
        private Percentage $feePercent,
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

    public function method(): PaymentMethod
    {
        return $this->method;
    }

    public function feePercent(): Percentage
    {
        return $this->feePercent;
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
        return 'payment.initiated';
    }

    public function payload(): array
    {
        return [
            'payment_id' => $this->id->toString(),
            'booking_id' => $this->bookingId->toString(),
            'gross_amount' => $this->gross->amount(),
            'currency' => $this->gross->currency(),
            'method' => $this->method->value,
            'fee_percent' => $this->feePercent->value(),
            'occurred_at' => $this->occurredAt->format(DATE_ATOM),
        ];
    }

    public static function fromPayload(array $payload): self
    {
        return new self(
            new PaymentId((string) $payload['payment_id']),
            new BookingId((string) $payload['booking_id']),
            Money::fromCents((int) $payload['gross_amount'], (string) $payload['currency']),
            PaymentMethod::from((string) $payload['method']),
            Percentage::fromInt((int) $payload['fee_percent']),
            new DateTimeImmutable((string) $payload['occurred_at']),
        );
    }
}
