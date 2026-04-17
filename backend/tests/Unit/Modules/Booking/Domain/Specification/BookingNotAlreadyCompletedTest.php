<?php

declare(strict_types=1);

use App\Modules\Booking\Domain\Entity\Booking;
use App\Modules\Booking\Domain\Specification\BookingNotAlreadyCompleted;
use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Modules\Booking\Domain\ValueObject\BookingStatus;
use App\Modules\Booking\Domain\ValueObject\BookingType;
use App\Modules\Booking\Domain\ValueObject\SlotId;
use App\Modules\Booking\Domain\ValueObject\TimeRange;
use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use App\Modules\Identity\Domain\ValueObject\UserId;

function makeBookingWithStatus(BookingStatus $status): Booking
{
    return Booking::reconstitute(
        id: BookingId::generate(),
        userId: UserId::generate(),
        serviceId: ServiceId::generate(),
        type: BookingType::TIME_SLOT,
        status: $status,
        slotId: SlotId::generate(),
        timeRange: new TimeRange(
            new DateTimeImmutable('+2 days'),
            new DateTimeImmutable('+2 days 1 hour'),
        ),
        dateRange: null,
        quantity: null,
        totalPrice: Money::fromCents(100000, 'RUB'),
        notes: null,
        createdAt: new DateTimeImmutable('-1 day'),
        updatedAt: new DateTimeImmutable,
    );
}

it('satisfies for non-completed statuses', function (BookingStatus $status): void {
    $spec = new BookingNotAlreadyCompleted;

    expect($spec->isSatisfiedBy(makeBookingWithStatus($status)))->toBeTrue();
})->with([
    'pending' => BookingStatus::PENDING,
    'confirmed' => BookingStatus::CONFIRMED,
    'cancelled' => BookingStatus::CANCELLED,
]);

it('fails for completed status', function (): void {
    $spec = new BookingNotAlreadyCompleted;

    expect($spec->isSatisfiedBy(makeBookingWithStatus(BookingStatus::COMPLETED)))->toBeFalse();
    expect($spec->failureReason())->toContain('completed');
});
