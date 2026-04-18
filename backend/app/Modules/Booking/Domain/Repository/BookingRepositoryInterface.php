<?php

declare(strict_types=1);

namespace App\Modules\Booking\Domain\Repository;

use App\Modules\Booking\Domain\Entity\Booking;
use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Modules\Booking\Domain\ValueObject\DateRange;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use App\Modules\Identity\Domain\ValueObject\UserId;

/**
 * Контракт persistence для Booking aggregate.
 */
interface BookingRepositoryInterface
{
    public function save(Booking $booking): void;

    public function findById(BookingId $id): ?Booking;

    /** @return Booking[] */
    public function findByUserId(UserId $userId, int $limit, int $offset): array;

    public function countByUserId(UserId $userId): int;

    public function countActiveByUserId(UserId $userId): int;

    /**
     * Сумма quantity по активным (PENDING/CONFIRMED) QUANTITY бронированиям,
     * пересекающимся с диапазоном. С $lockForUpdate держит row-lock до конца транзакции.
     */
    public function sumActiveQuantityOverlapping(ServiceId $serviceId, DateRange $range, bool $lockForUpdate = false): int;
}
