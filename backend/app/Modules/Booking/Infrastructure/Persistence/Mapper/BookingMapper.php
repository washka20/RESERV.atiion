<?php

declare(strict_types=1);

namespace App\Modules\Booking\Infrastructure\Persistence\Mapper;

use App\Modules\Booking\Domain\Entity\Booking;
use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Modules\Booking\Domain\ValueObject\BookingStatus;
use App\Modules\Booking\Domain\ValueObject\BookingType;
use App\Modules\Booking\Domain\ValueObject\DateRange;
use App\Modules\Booking\Domain\ValueObject\Quantity;
use App\Modules\Booking\Domain\ValueObject\SlotId;
use App\Modules\Booking\Domain\ValueObject\TimeRange;
use App\Modules\Booking\Infrastructure\Persistence\Model\BookingModel;
use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use App\Modules\Identity\Domain\ValueObject\UserId;

/**
 * Bidirectional mapper Booking <-> BookingModel.
 */
final class BookingMapper
{
    public static function toDomain(BookingModel $model): Booking
    {
        return Booking::reconstitute(
            id: new BookingId($model->id),
            userId: new UserId($model->user_id),
            serviceId: new ServiceId($model->service_id),
            type: BookingType::from($model->type),
            status: BookingStatus::from($model->status),
            slotId: $model->slot_id !== null ? new SlotId($model->slot_id) : null,
            timeRange: $model->start_at !== null && $model->end_at !== null
                ? new TimeRange($model->start_at, $model->end_at)
                : null,
            dateRange: $model->check_in !== null && $model->check_out !== null
                ? new DateRange($model->check_in, $model->check_out)
                : null,
            quantity: $model->quantity !== null ? new Quantity((int) $model->quantity) : null,
            totalPrice: Money::fromCents((int) round(((float) $model->total_price_amount) * 100), $model->total_price_currency),
            notes: $model->notes,
            createdAt: $model->created_at?->toDateTimeImmutable() ?? new \DateTimeImmutable,
            updatedAt: $model->updated_at?->toDateTimeImmutable() ?? new \DateTimeImmutable,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function toArray(Booking $booking): array
    {
        return [
            'id' => $booking->id->toString(),
            'user_id' => $booking->userId->toString(),
            'service_id' => $booking->serviceId->toString(),
            'type' => $booking->type->value,
            'status' => $booking->status()->value,
            'slot_id' => $booking->slotId?->toString(),
            'start_at' => $booking->timeRange?->startAt,
            'end_at' => $booking->timeRange?->endAt,
            'check_in' => $booking->dateRange?->checkIn->format('Y-m-d'),
            'check_out' => $booking->dateRange?->checkOut->format('Y-m-d'),
            'quantity' => $booking->quantity?->value,
            'total_price_amount' => $booking->totalPrice->amount() / 100,
            'total_price_currency' => $booking->totalPrice->currency(),
            'notes' => $booking->notes,
            'updated_at' => $booking->updatedAt(),
        ];
    }
}
