<?php

declare(strict_types=1);

namespace App\Modules\Payment\Domain\Event;

use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Modules\Payment\Domain\ValueObject\PaymentId;
use App\Shared\Domain\DomainEvent;
use DateTimeImmutable;

/**
 * Платёж провалился (статус FAILED).
 */
final readonly class PaymentFailed implements DomainEvent
{
    public function __construct(
        private PaymentId $id,
        private BookingId $bookingId,
        private string $reason,
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

    public function reason(): string
    {
        return $this->reason;
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
        return 'payment.failed';
    }

    public function payload(): array
    {
        return [
            'payment_id' => $this->id->toString(),
            'booking_id' => $this->bookingId->toString(),
            'reason' => $this->reason,
            'occurred_at' => $this->occurredAt->format(DATE_ATOM),
        ];
    }
}
