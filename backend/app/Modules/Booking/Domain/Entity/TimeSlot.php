<?php

declare(strict_types=1);

namespace App\Modules\Booking\Domain\Entity;

use App\Modules\Booking\Domain\Event\TimeSlotReleased;
use App\Modules\Booking\Domain\Event\TimeSlotReserved;
use App\Modules\Booking\Domain\Exception\SlotAlreadyBookedException;
use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Modules\Booking\Domain\ValueObject\SlotId;
use App\Modules\Booking\Domain\ValueObject\TimeRange;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use App\Shared\Domain\AggregateRoot;
use DateTimeImmutable;

/**
 * Временной слот для TIME_SLOT услуги. Aggregate root Booking BC.
 *
 * Защищает инвариант "один слот = одно бронирование" на уровне сущности.
 * Фактическая защита от race conditions — в репозитории через atomic UPDATE.
 */
final class TimeSlot extends AggregateRoot
{
    private function __construct(
        public readonly SlotId $id,
        public readonly ServiceId $serviceId,
        public readonly TimeRange $range,
        private bool $isBooked,
        private ?BookingId $bookingId,
    ) {}

    /**
     * Создаёт новый (свободный) слот.
     *
     * @throws \InvalidArgumentException если endAt <= startAt (через TimeRange)
     */
    public static function create(
        SlotId $id,
        ServiceId $serviceId,
        DateTimeImmutable $startAt,
        DateTimeImmutable $endAt,
    ): self {
        return new self($id, $serviceId, new TimeRange($startAt, $endAt), false, null);
    }

    /**
     * Восстанавливает слот из persistence без emission событий.
     */
    public static function reconstitute(
        SlotId $id,
        ServiceId $serviceId,
        DateTimeImmutable $startAt,
        DateTimeImmutable $endAt,
        bool $isBooked,
        ?BookingId $bookingId,
    ): self {
        return new self($id, $serviceId, new TimeRange($startAt, $endAt), $isBooked, $bookingId);
    }

    public function isBooked(): bool
    {
        return $this->isBooked;
    }

    public function bookingId(): ?BookingId
    {
        return $this->bookingId;
    }

    /**
     * Резервирует слот под конкретное бронирование.
     *
     * @throws SlotAlreadyBookedException если слот уже занят
     */
    public function reserve(BookingId $bookingId): void
    {
        if ($this->isBooked) {
            throw SlotAlreadyBookedException::forSlotId($this->id);
        }
        $this->isBooked = true;
        $this->bookingId = $bookingId;
        $this->recordEvent(new TimeSlotReserved($this->id, $bookingId, new DateTimeImmutable));
    }

    /**
     * Освобождает слот. Идемпотентна — вызов на свободном слоте не ошибка.
     */
    public function release(): void
    {
        $this->isBooked = false;
        $this->bookingId = null;
        $this->recordEvent(new TimeSlotReleased($this->id, new DateTimeImmutable));
    }
}
