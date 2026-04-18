<?php

declare(strict_types=1);

namespace App\Modules\Booking\Infrastructure\Persistence\Mapper;

use App\Modules\Booking\Domain\Entity\TimeSlot;
use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Modules\Booking\Domain\ValueObject\SlotId;
use App\Modules\Booking\Infrastructure\Persistence\Model\TimeSlotModel;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;

/**
 * Bidirectional mapper TimeSlot <-> TimeSlotModel.
 */
final class TimeSlotMapper
{
    public static function toDomain(TimeSlotModel $model): TimeSlot
    {
        return TimeSlot::reconstitute(
            id: new SlotId($model->id),
            serviceId: new ServiceId($model->service_id),
            startAt: $model->start_at,
            endAt: $model->end_at,
            isBooked: (bool) $model->is_booked,
            bookingId: $model->booking_id !== null ? new BookingId($model->booking_id) : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function toArray(TimeSlot $slot): array
    {
        return [
            'id' => $slot->id->toString(),
            'service_id' => $slot->serviceId->toString(),
            'start_at' => $slot->range->startAt,
            'end_at' => $slot->range->endAt,
            'is_booked' => $slot->isBooked(),
            'booking_id' => $slot->bookingId()?->toString(),
            'updated_at' => new \DateTimeImmutable,
        ];
    }
}
