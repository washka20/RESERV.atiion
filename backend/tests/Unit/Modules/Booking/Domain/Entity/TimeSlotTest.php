<?php

declare(strict_types=1);

use App\Modules\Booking\Domain\Entity\TimeSlot;
use App\Modules\Booking\Domain\Event\TimeSlotReleased;
use App\Modules\Booking\Domain\Event\TimeSlotReserved;
use App\Modules\Booking\Domain\Exception\SlotAlreadyBookedException;
use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Modules\Booking\Domain\ValueObject\SlotId;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;

function containsSlotEvent(array $events, string $class): bool
{
    foreach ($events as $event) {
        if ($event instanceof $class) {
            return true;
        }
    }

    return false;
}

it('creates a free slot', function (): void {
    $slot = TimeSlot::create(
        SlotId::generate(),
        ServiceId::generate(),
        new DateTimeImmutable('+2 days 10:00'),
        new DateTimeImmutable('+2 days 11:00'),
    );

    expect($slot->isBooked())->toBeFalse();
    expect($slot->bookingId())->toBeNull();
});

it('rejects slot with invalid time range', function (): void {
    TimeSlot::create(
        SlotId::generate(),
        ServiceId::generate(),
        new DateTimeImmutable('+2 days 11:00'),
        new DateTimeImmutable('+2 days 10:00'),
    );
})->throws(InvalidArgumentException::class);

it('reserves a free slot and records TimeSlotReserved', function (): void {
    $slot = TimeSlot::create(
        SlotId::generate(),
        ServiceId::generate(),
        new DateTimeImmutable('+2 days 10:00'),
        new DateTimeImmutable('+2 days 11:00'),
    );
    $bookingId = BookingId::generate();

    $slot->reserve($bookingId);

    expect($slot->isBooked())->toBeTrue();
    expect($slot->bookingId()->equals($bookingId))->toBeTrue();
    expect(containsSlotEvent($slot->pullDomainEvents(), TimeSlotReserved::class))->toBeTrue();
});

it('rejects reserving an already-booked slot', function (): void {
    $slot = TimeSlot::create(
        SlotId::generate(),
        ServiceId::generate(),
        new DateTimeImmutable('+2 days 10:00'),
        new DateTimeImmutable('+2 days 11:00'),
    );
    $slot->reserve(BookingId::generate());

    $slot->reserve(BookingId::generate());
})->throws(SlotAlreadyBookedException::class);

it('releases a reserved slot and records TimeSlotReleased', function (): void {
    $slot = TimeSlot::create(
        SlotId::generate(),
        ServiceId::generate(),
        new DateTimeImmutable('+2 days 10:00'),
        new DateTimeImmutable('+2 days 11:00'),
    );
    $slot->reserve(BookingId::generate());
    $slot->pullDomainEvents();

    $slot->release();

    expect($slot->isBooked())->toBeFalse();
    expect($slot->bookingId())->toBeNull();
    expect(containsSlotEvent($slot->pullDomainEvents(), TimeSlotReleased::class))->toBeTrue();
});

it('reconstitutes slot from persistence', function (): void {
    $slotId = SlotId::generate();
    $bookingId = BookingId::generate();
    $slot = TimeSlot::reconstitute(
        $slotId,
        ServiceId::generate(),
        new DateTimeImmutable('+2 days 10:00'),
        new DateTimeImmutable('+2 days 11:00'),
        true,
        $bookingId,
    );

    expect($slot->isBooked())->toBeTrue();
    expect($slot->bookingId()->equals($bookingId))->toBeTrue();
    expect($slot->pullDomainEvents())->toBe([]);
});
