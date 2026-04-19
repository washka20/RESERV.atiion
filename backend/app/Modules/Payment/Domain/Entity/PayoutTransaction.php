<?php

declare(strict_types=1);

namespace App\Modules\Payment\Domain\Entity;

use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Identity\Domain\ValueObject\OrganizationId;
use App\Modules\Payment\Domain\Event\PayoutMarkedPaid;
use App\Modules\Payment\Domain\Event\PayoutTransactionCreated;
use App\Modules\Payment\Domain\Exception\PayoutAlreadyProcessedException;
use App\Modules\Payment\Domain\ValueObject\PaymentId;
use App\Modules\Payment\Domain\ValueObject\PayoutStatus;
use App\Modules\Payment\Domain\ValueObject\PayoutTransactionId;
use App\Shared\Domain\AggregateRoot;
use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Выплата провайдеру. Aggregate root Payout BC внутри Payment модуля.
 *
 * Машина состояний: PENDING → PROCESSING → PAID|FAILED.
 * Инвариант gross = platformFee + net проверяется в фабричном методе create.
 */
final class PayoutTransaction extends AggregateRoot
{
    private function __construct(
        private readonly PayoutTransactionId $id,
        private readonly BookingId $bookingId,
        private readonly OrganizationId $organizationId,
        private readonly PaymentId $paymentId,
        private readonly Money $gross,
        private readonly Money $platformFee,
        private readonly Money $net,
        private PayoutStatus $status,
        private ?DateTimeImmutable $scheduledAt,
        private ?DateTimeImmutable $paidAt,
        private ?string $failureReason,
    ) {}

    /**
     * Создаёт выплату в статусе PENDING.
     *
     * @throws InvalidArgumentException если gross != platformFee + net (разные валюты — из Money::add)
     */
    public static function create(
        PayoutTransactionId $id,
        BookingId $bookingId,
        OrganizationId $organizationId,
        PaymentId $paymentId,
        Money $gross,
        Money $platformFee,
        Money $net,
    ): self {
        $expected = $platformFee->add($net);

        if (! $gross->equals($expected)) {
            throw new InvalidArgumentException('gross must equal fee + net');
        }

        $payout = new self(
            $id,
            $bookingId,
            $organizationId,
            $paymentId,
            $gross,
            $platformFee,
            $net,
            PayoutStatus::PENDING,
            null,
            null,
            null,
        );

        $payout->recordEvent(new PayoutTransactionCreated(
            $id,
            $bookingId,
            $organizationId,
            $paymentId,
            $gross,
            $platformFee,
            $net,
            new DateTimeImmutable,
        ));

        return $payout;
    }

    /**
     * Восстанавливает PayoutTransaction из persistence (без записи событий).
     * Используется Mapper при чтении из БД.
     */
    public static function reconstitute(
        PayoutTransactionId $id,
        BookingId $bookingId,
        OrganizationId $organizationId,
        PaymentId $paymentId,
        Money $gross,
        Money $platformFee,
        Money $net,
        PayoutStatus $status,
        ?DateTimeImmutable $scheduledAt,
        ?DateTimeImmutable $paidAt,
        ?string $failureReason,
    ): self {
        return new self(
            $id,
            $bookingId,
            $organizationId,
            $paymentId,
            $gross,
            $platformFee,
            $net,
            $status,
            $scheduledAt,
            $paidAt,
            $failureReason,
        );
    }

    /**
     * Устанавливает запланированную дату выплаты.
     */
    public function schedule(DateTimeImmutable $at): void
    {
        $this->scheduledAt = $at;
    }

    /**
     * Переводит PENDING → PROCESSING.
     *
     * @throws PayoutAlreadyProcessedException если текущий статус не PENDING
     */
    public function moveToProcessing(): void
    {
        if ($this->status !== PayoutStatus::PENDING) {
            throw PayoutAlreadyProcessedException::from($this->status);
        }

        $this->status = PayoutStatus::PROCESSING;
    }

    /**
     * Переводит PROCESSING → PAID, фиксирует paidAt и публикует PayoutMarkedPaid.
     *
     * @throws PayoutAlreadyProcessedException если текущий статус не PROCESSING
     */
    public function markPaid(): void
    {
        if ($this->status !== PayoutStatus::PROCESSING) {
            throw PayoutAlreadyProcessedException::from($this->status);
        }

        $this->status = PayoutStatus::PAID;
        $this->paidAt = new DateTimeImmutable;

        $this->recordEvent(new PayoutMarkedPaid(
            $this->id,
            $this->organizationId,
            $this->net,
            $this->paidAt,
        ));
    }

    /**
     * Переводит PROCESSING → FAILED с причиной.
     *
     * @throws PayoutAlreadyProcessedException если текущий статус не PROCESSING
     */
    public function markFailed(string $reason): void
    {
        if ($this->status !== PayoutStatus::PROCESSING) {
            throw PayoutAlreadyProcessedException::from($this->status);
        }

        $this->status = PayoutStatus::FAILED;
        $this->failureReason = $reason;
    }

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

    public function grossAmount(): Money
    {
        return $this->gross;
    }

    public function platformFee(): Money
    {
        return $this->platformFee;
    }

    public function netAmount(): Money
    {
        return $this->net;
    }

    public function status(): PayoutStatus
    {
        return $this->status;
    }

    public function scheduledAt(): ?DateTimeImmutable
    {
        return $this->scheduledAt;
    }

    public function paidAt(): ?DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function failureReason(): ?string
    {
        return $this->failureReason;
    }
}
