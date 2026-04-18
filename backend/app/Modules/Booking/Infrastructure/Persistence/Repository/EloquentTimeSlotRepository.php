<?php

declare(strict_types=1);

namespace App\Modules\Booking\Infrastructure\Persistence\Repository;

use App\Modules\Booking\Domain\Entity\TimeSlot;
use App\Modules\Booking\Domain\Repository\TimeSlotRepositoryInterface;
use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Modules\Booking\Domain\ValueObject\SlotId;
use App\Modules\Booking\Infrastructure\Persistence\Mapper\TimeSlotMapper;
use App\Modules\Booking\Infrastructure\Persistence\Model\TimeSlotModel;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use DateTimeImmutable;

/**
 * Eloquent реализация TimeSlotRepositoryInterface.
 *
 * markAsBooked делает атомарный UPDATE ... WHERE is_booked=false. Если affected rows=1 —
 * мы первыми зарезервировали слот. 0 — кто-то успел раньше (без исключения — caller интерпретирует).
 */
final class EloquentTimeSlotRepository implements TimeSlotRepositoryInterface
{
    public function save(TimeSlot $slot): void
    {
        TimeSlotModel::query()->updateOrCreate(
            ['id' => $slot->id->toString()],
            TimeSlotMapper::toArray($slot),
        );
    }

    public function saveMany(array $slots): void
    {
        if ($slots === []) {
            return;
        }
        $now = new DateTimeImmutable;
        $rows = array_map(static function (TimeSlot $s) use ($now): array {
            $data = TimeSlotMapper::toArray($s);
            $data['created_at'] = $now;

            return $data;
        }, $slots);
        TimeSlotModel::query()->insert($rows);
    }

    public function findById(SlotId $id): ?TimeSlot
    {
        $model = TimeSlotModel::query()->find($id->toString());

        return $model !== null ? TimeSlotMapper::toDomain($model) : null;
    }

    public function findByServiceAndDate(ServiceId $serviceId, DateTimeImmutable $date): array
    {
        return TimeSlotModel::query()
            ->where('service_id', $serviceId->toString())
            ->whereDate('start_at', $date->format('Y-m-d'))
            ->orderBy('start_at')
            ->get()
            ->map(static fn (TimeSlotModel $m): TimeSlot => TimeSlotMapper::toDomain($m))
            ->all();
    }

    public function findAvailableByServiceAndDate(ServiceId $serviceId, DateTimeImmutable $date): array
    {
        return TimeSlotModel::query()
            ->where('service_id', $serviceId->toString())
            ->where('is_booked', false)
            ->whereDate('start_at', $date->format('Y-m-d'))
            ->orderBy('start_at')
            ->get()
            ->map(static fn (TimeSlotModel $m): TimeSlot => TimeSlotMapper::toDomain($m))
            ->all();
    }

    public function markAsBooked(SlotId $slotId, BookingId $bookingId): bool
    {
        $affected = TimeSlotModel::query()
            ->where('id', $slotId->toString())
            ->where('is_booked', false)
            ->update([
                'is_booked' => true,
                'booking_id' => $bookingId->toString(),
            ]);

        return $affected === 1;
    }

    public function markAsFree(SlotId $slotId): void
    {
        TimeSlotModel::query()
            ->where('id', $slotId->toString())
            ->update([
                'is_booked' => false,
                'booking_id' => null,
            ]);
    }
}
