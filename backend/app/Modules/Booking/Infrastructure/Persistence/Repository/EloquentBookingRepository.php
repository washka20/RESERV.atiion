<?php

declare(strict_types=1);

namespace App\Modules\Booking\Infrastructure\Persistence\Repository;

use App\Modules\Booking\Domain\Entity\Booking;
use App\Modules\Booking\Domain\Repository\BookingRepositoryInterface;
use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Modules\Booking\Domain\ValueObject\DateRange;
use App\Modules\Booking\Infrastructure\Persistence\Mapper\BookingMapper;
use App\Modules\Booking\Infrastructure\Persistence\Model\BookingModel;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use App\Modules\Identity\Domain\ValueObject\UserId;

/**
 * Eloquent реализация BookingRepositoryInterface.
 *
 * sumActiveQuantityOverlapping с $lockForUpdate=true держит row-lock —
 * используется в транзакции CreateBooking для защиты от race conditions.
 */
final class EloquentBookingRepository implements BookingRepositoryInterface
{
    public function save(Booking $booking): void
    {
        BookingModel::query()->updateOrCreate(
            ['id' => $booking->id->toString()],
            BookingMapper::toArray($booking),
        );
    }

    public function findById(BookingId $id): ?Booking
    {
        $model = BookingModel::query()->find($id->toString());

        return $model !== null ? BookingMapper::toDomain($model) : null;
    }

    public function findByUserId(UserId $userId, int $limit, int $offset): array
    {
        return BookingModel::query()
            ->where('user_id', $userId->toString())
            ->orderByDesc('created_at')
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->map(static fn (BookingModel $m): Booking => BookingMapper::toDomain($m))
            ->all();
    }

    public function countByUserId(UserId $userId): int
    {
        return BookingModel::query()
            ->where('user_id', $userId->toString())
            ->count();
    }

    public function countActiveByUserId(UserId $userId): int
    {
        return BookingModel::query()
            ->where('user_id', $userId->toString())
            ->whereIn('status', ['pending', 'confirmed'])
            ->count();
    }

    public function sumActiveQuantityOverlapping(ServiceId $serviceId, DateRange $range, bool $lockForUpdate = false): int
    {
        $query = BookingModel::query()
            ->select(['id', 'quantity'])
            ->where('service_id', $serviceId->toString())
            ->where('type', 'quantity')
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('check_in', '<', $range->checkOut->format('Y-m-d'))
            ->where('check_out', '>', $range->checkIn->format('Y-m-d'));

        if ($lockForUpdate) {
            // PG запрещает SELECT SUM(...) FOR UPDATE — row-lock несовместим с агрегацией.
            // Берём строки с lock, суммируем в PHP: lock держится до конца транзакции.
            $query->lockForUpdate();
        }

        $rows = $query->get();

        return (int) $rows->sum('quantity');
    }
}
