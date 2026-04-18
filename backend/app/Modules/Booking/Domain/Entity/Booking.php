<?php

declare(strict_types=1);

namespace App\Modules\Booking\Domain\Entity;

use App\Modules\Booking\Domain\Event\BookingCancelled;
use App\Modules\Booking\Domain\Event\BookingCompleted;
use App\Modules\Booking\Domain\Event\BookingConfirmed;
use App\Modules\Booking\Domain\Event\BookingCreated;
use App\Modules\Booking\Domain\Exception\CancellationNotAllowedException;
use App\Modules\Booking\Domain\Exception\InvalidBookingStateTransitionException;
use App\Modules\Booking\Domain\Specification\CancellationPolicy;
use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Modules\Booking\Domain\ValueObject\BookingStatus;
use App\Modules\Booking\Domain\ValueObject\BookingType;
use App\Modules\Booking\Domain\ValueObject\DateRange;
use App\Modules\Booking\Domain\ValueObject\Quantity;
use App\Modules\Booking\Domain\ValueObject\SlotId;
use App\Modules\Booking\Domain\ValueObject\TimeRange;
use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use App\Modules\Identity\Domain\ValueObject\UserId;
use App\Shared\Domain\AggregateRoot;
use DateTimeImmutable;

/**
 * Бронирование. Aggregate root Booking BC.
 *
 * Поддерживает два типа: TIME_SLOT (конкретный слот) и QUANTITY (количество на диапазон дат).
 * Инварианты проверяются в фабричных методах createTimeSlotBooking / createQuantityBooking.
 */
final class Booking extends AggregateRoot
{
    private function __construct(
        public readonly BookingId $id,
        public readonly UserId $userId,
        public readonly ServiceId $serviceId,
        public readonly BookingType $type,
        private BookingStatus $status,
        public readonly ?SlotId $slotId,
        public readonly ?TimeRange $timeRange,
        public readonly ?DateRange $dateRange,
        public readonly ?Quantity $quantity,
        public readonly Money $totalPrice,
        public readonly ?string $notes,
        public readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {}

    /**
     * Создаёт TIME_SLOT бронирование — привязано к конкретному TimeSlot.
     */
    public static function createTimeSlotBooking(
        BookingId $id,
        UserId $userId,
        ServiceId $serviceId,
        SlotId $slotId,
        TimeRange $timeRange,
        Money $totalPrice,
        ?string $notes = null,
    ): self {
        $now = new DateTimeImmutable;
        $booking = new self(
            id: $id,
            userId: $userId,
            serviceId: $serviceId,
            type: BookingType::TIME_SLOT,
            status: BookingStatus::PENDING,
            slotId: $slotId,
            timeRange: $timeRange,
            dateRange: null,
            quantity: null,
            totalPrice: $totalPrice,
            notes: $notes,
            createdAt: $now,
            updatedAt: $now,
        );
        $booking->recordEvent(new BookingCreated($id, $userId, $serviceId, BookingType::TIME_SLOT, $now));

        return $booking;
    }

    /**
     * Создаёт QUANTITY бронирование — диапазон дат + количество единиц.
     */
    public static function createQuantityBooking(
        BookingId $id,
        UserId $userId,
        ServiceId $serviceId,
        DateRange $dateRange,
        Quantity $quantity,
        Money $totalPrice,
        ?string $notes = null,
    ): self {
        $now = new DateTimeImmutable;
        $booking = new self(
            id: $id,
            userId: $userId,
            serviceId: $serviceId,
            type: BookingType::QUANTITY,
            status: BookingStatus::PENDING,
            slotId: null,
            timeRange: null,
            dateRange: $dateRange,
            quantity: $quantity,
            totalPrice: $totalPrice,
            notes: $notes,
            createdAt: $now,
            updatedAt: $now,
        );
        $booking->recordEvent(new BookingCreated($id, $userId, $serviceId, BookingType::QUANTITY, $now));

        return $booking;
    }

    /**
     * Восстанавливает Booking из persistence без события BookingCreated.
     * Используется маппером репозитория.
     */
    public static function reconstitute(
        BookingId $id,
        UserId $userId,
        ServiceId $serviceId,
        BookingType $type,
        BookingStatus $status,
        ?SlotId $slotId,
        ?TimeRange $timeRange,
        ?DateRange $dateRange,
        ?Quantity $quantity,
        Money $totalPrice,
        ?string $notes,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            $id,
            $userId,
            $serviceId,
            $type,
            $status,
            $slotId,
            $timeRange,
            $dateRange,
            $quantity,
            $totalPrice,
            $notes,
            $createdAt,
            $updatedAt,
        );
    }

    public function status(): BookingStatus
    {
        return $this->status;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Переход PENDING -> CONFIRMED. Идемпотентность не поддерживается.
     *
     * @throws DomainException если статус не PENDING
     */
    public function confirm(): void
    {
        if ($this->status !== BookingStatus::PENDING) {
            throw InvalidBookingStateTransitionException::withMessage(
                sprintf('Only pending booking can be confirmed; current status: %s', $this->status->value),
            );
        }
        $this->status = BookingStatus::CONFIRMED;
        $this->updatedAt = new DateTimeImmutable;
        $this->recordEvent(new BookingConfirmed($this->id, $this->updatedAt));
    }

    /**
     * Переход -> CANCELLED через CancellationPolicy. Политика инкапсулирует
     * "можно ли отменить" (окно, уже завершено и т.д.).
     *
     * @throws CancellationNotAllowedException если политика не удовлетворена
     */
    public function cancel(CancellationPolicy $policy): void
    {
        if (! $policy->isSatisfiedBy($this)) {
            throw CancellationNotAllowedException::withReason(
                $policy->failureReason() ?? 'policy not satisfied',
            );
        }
        $this->status = BookingStatus::CANCELLED;
        $this->updatedAt = new DateTimeImmutable;
        $this->recordEvent(new BookingCancelled($this->id, $this->type, $this->slotId, $this->updatedAt));
    }

    /**
     * Переход CONFIRMED -> COMPLETED.
     *
     * @throws DomainException если статус не CONFIRMED
     */
    public function complete(): void
    {
        if ($this->status !== BookingStatus::CONFIRMED) {
            throw InvalidBookingStateTransitionException::withMessage(
                sprintf('Only confirmed booking can be completed; current status: %s', $this->status->value),
            );
        }
        $this->status = BookingStatus::COMPLETED;
        $this->updatedAt = new DateTimeImmutable;
        $this->recordEvent(new BookingCompleted($this->id, $this->updatedAt));
    }

    public function isActive(): bool
    {
        return $this->status->isActive();
    }
}
