<?php

declare(strict_types=1);

namespace App\Modules\Booking\Domain\Repository;

use App\Modules\Booking\Domain\Entity\TimeSlot;
use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Modules\Booking\Domain\ValueObject\SlotId;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use DateTimeImmutable;

/**
 * Контракт persistence для TimeSlot aggregate.
 */
interface TimeSlotRepositoryInterface
{
    public function save(TimeSlot $slot): void;

    /** @param TimeSlot[] $slots */
    public function saveMany(array $slots): void;

    public function findById(SlotId $id): ?TimeSlot;

    /** @return TimeSlot[] */
    public function findByServiceAndDate(ServiceId $serviceId, DateTimeImmutable $date): array;

    /** @return TimeSlot[] */
    public function findAvailableByServiceAndDate(ServiceId $serviceId, DateTimeImmutable $date): array;

    /**
     * Атомарный conditional UPDATE: помечает слот занятым только если он был свободен.
     *
     * @return bool true если UPDATE затронул строку (мы первыми зарезервировали),
     *              false если кто-то успел раньше
     */
    public function markAsBooked(SlotId $slotId, BookingId $bookingId): bool;

    public function markAsFree(SlotId $slotId): void;
}
