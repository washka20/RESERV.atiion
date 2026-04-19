<?php

declare(strict_types=1);

namespace App\Modules\Payment\Domain\Entity;

use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Payment\Domain\Event\PaymentFailed;
use App\Modules\Payment\Domain\Event\PaymentInitiated;
use App\Modules\Payment\Domain\Event\PaymentReceived;
use App\Modules\Payment\Domain\Event\PaymentRefunded;
use App\Modules\Payment\Domain\Exception\InvalidPaymentAmountException;
use App\Modules\Payment\Domain\Exception\PaymentAlreadyProcessedException;
use App\Modules\Payment\Domain\ValueObject\MarketplaceFee;
use App\Modules\Payment\Domain\ValueObject\PaymentId;
use App\Modules\Payment\Domain\ValueObject\PaymentMethod;
use App\Modules\Payment\Domain\ValueObject\PaymentStatus;
use App\Modules\Payment\Domain\ValueObject\Percentage;
use App\Shared\Domain\AggregateRoot;
use DateTimeImmutable;

/**
 * Платёж. Aggregate root Payment BC.
 *
 * Машина состояний: PENDING → PAID → REFUNDED, PENDING → FAILED.
 * Marketplace fee (gross → platformFee + net) рассчитывается через MarketplaceFee VO.
 */
final class Payment extends AggregateRoot
{
    private function __construct(
        private readonly PaymentId $id,
        private readonly BookingId $bookingId,
        private readonly Money $gross,
        private readonly PaymentMethod $method,
        private readonly Percentage $feePercent,
        private PaymentStatus $status,
        private ?string $providerRef,
        private ?DateTimeImmutable $paidAt,
    ) {}

    /**
     * Инициирует новый платёж в статусе PENDING.
     *
     * @throws InvalidPaymentAmountException если gross не положительный
     */
    public static function initiate(
        PaymentId $id,
        BookingId $bookingId,
        Money $gross,
        PaymentMethod $method,
        Percentage $feePercent,
    ): self {
        if ($gross->amount() <= 0) {
            throw new InvalidPaymentAmountException('Payment gross amount must be positive');
        }

        $payment = new self(
            $id,
            $bookingId,
            $gross,
            $method,
            $feePercent,
            PaymentStatus::PENDING,
            null,
            null,
        );

        $payment->recordEvent(new PaymentInitiated(
            $id,
            $bookingId,
            $gross,
            $method,
            $feePercent,
            new DateTimeImmutable(),
        ));

        return $payment;
    }

    /**
     * Восстанавливает Payment из persistence (без записи событий).
     * Используется Mapper при чтении из БД.
     */
    public static function reconstitute(
        PaymentId $id,
        BookingId $bookingId,
        Money $gross,
        PaymentMethod $method,
        Percentage $feePercent,
        PaymentStatus $status,
        ?string $providerRef,
        ?DateTimeImmutable $paidAt,
    ): self {
        return new self(
            $id,
            $bookingId,
            $gross,
            $method,
            $feePercent,
            $status,
            $providerRef,
            $paidAt,
        );
    }

    /**
     * Отмечает платёж как успешно проведённый.
     *
     * @throws PaymentAlreadyProcessedException если текущий статус не PENDING
     */
    public function markPaid(string $providerRef): void
    {
        if ($this->status !== PaymentStatus::PENDING) {
            throw PaymentAlreadyProcessedException::from($this->status);
        }

        $this->status = PaymentStatus::PAID;
        $this->providerRef = $providerRef;
        $this->paidAt = new DateTimeImmutable();

        $fee = $this->calculateFee();

        $this->recordEvent(new PaymentReceived(
            $this->id,
            $this->bookingId,
            $fee->gross(),
            $fee->fee(),
            $fee->net(),
            $providerRef,
            $this->paidAt,
        ));
    }

    /**
     * Отмечает платёж как провалившийся.
     *
     * @throws PaymentAlreadyProcessedException если текущий статус не PENDING
     */
    public function markFailed(string $reason): void
    {
        if ($this->status !== PaymentStatus::PENDING) {
            throw PaymentAlreadyProcessedException::from($this->status);
        }

        $this->status = PaymentStatus::FAILED;

        $this->recordEvent(new PaymentFailed(
            $this->id,
            $this->bookingId,
            $reason,
            new DateTimeImmutable(),
        ));
    }

    /**
     * Возвращает платёж (refund).
     *
     * @throws PaymentAlreadyProcessedException если текущий статус не PAID
     */
    public function refund(): void
    {
        if ($this->status !== PaymentStatus::PAID) {
            throw PaymentAlreadyProcessedException::from($this->status);
        }

        $this->status = PaymentStatus::REFUNDED;

        $this->recordEvent(new PaymentRefunded(
            $this->id,
            $this->bookingId,
            $this->gross,
            new DateTimeImmutable(),
        ));
    }

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

    /**
     * Комиссия площадки (делегат к MarketplaceFee VO).
     */
    public function platformFee(): Money
    {
        return $this->calculateFee()->fee();
    }

    /**
     * Чистая выплата провайдеру после вычета комиссии.
     */
    public function net(): Money
    {
        return $this->calculateFee()->net();
    }

    public function status(): PaymentStatus
    {
        return $this->status;
    }

    public function method(): PaymentMethod
    {
        return $this->method;
    }

    public function feePercent(): Percentage
    {
        return $this->feePercent;
    }

    public function providerRef(): ?string
    {
        return $this->providerRef;
    }

    public function paidAt(): ?DateTimeImmutable
    {
        return $this->paidAt;
    }

    private function calculateFee(): MarketplaceFee
    {
        return MarketplaceFee::calculate($this->gross, $this->feePercent);
    }
}
