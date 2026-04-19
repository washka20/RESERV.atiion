<?php

declare(strict_types=1);

use App\Modules\Booking\Domain\Entity\TimeSlot;
use App\Modules\Booking\Domain\Repository\BookingRepositoryInterface;
use App\Modules\Booking\Domain\Repository\TimeSlotRepositoryInterface;
use App\Modules\Booking\Domain\Service\AvailabilityChecker;
use App\Modules\Booking\Domain\Service\QuantityAvailabilityStrategy;
use App\Modules\Booking\Domain\Service\TimeSlotAvailabilityStrategy;
use App\Modules\Booking\Domain\ValueObject\SlotId;
use App\Modules\Catalog\Domain\Entity\Service;
use App\Modules\Catalog\Domain\Repository\ServiceRepositoryInterface;
use App\Modules\Catalog\Domain\ValueObject\CategoryId;
use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use App\Modules\Catalog\Domain\ValueObject\ServiceType;
use App\Modules\Identity\Domain\ValueObject\OrganizationId;

it('TimeSlot strategy returns available slots on date', function (): void {
    $serviceId = ServiceId::generate();
    $slot1 = TimeSlot::create(
        SlotId::generate(),
        $serviceId,
        new DateTimeImmutable('2026-05-01 10:00'),
        new DateTimeImmutable('2026-05-01 11:00'),
    );
    $slot2 = TimeSlot::create(
        SlotId::generate(),
        $serviceId,
        new DateTimeImmutable('2026-05-01 12:00'),
        new DateTimeImmutable('2026-05-01 13:00'),
    );

    $repo = mock(TimeSlotRepositoryInterface::class);
    $repo->shouldReceive('findAvailableByServiceAndDate')->andReturn([$slot1, $slot2]);

    $result = (new TimeSlotAvailabilityStrategy($repo))->check($serviceId, ['date' => '2026-05-01']);

    expect($result->available)->toBeTrue();
    expect($result->details['slots'])->toHaveCount(2);
});

it('TimeSlot strategy reports unavailable when no slots', function (): void {
    $repo = mock(TimeSlotRepositoryInterface::class);
    $repo->shouldReceive('findAvailableByServiceAndDate')->andReturn([]);

    $result = (new TimeSlotAvailabilityStrategy($repo))->check(
        ServiceId::generate(),
        ['date' => '2026-05-01'],
    );

    expect($result->available)->toBeFalse();
    expect($result->details['slots'])->toBe([]);
});

it('Quantity strategy computes available quantity', function (): void {
    $serviceId = ServiceId::generate();
    $service = Service::createQuantity(
        id: $serviceId,
        name: 'Room',
        description: 'Hotel room',
        price: Money::fromCents(100000, 'RUB'),
        totalQuantity: 10,
        categoryId: CategoryId::generate(),
        subcategoryId: null,
        organizationId: OrganizationId::generate(),
    );

    $serviceRepo = mock(ServiceRepositoryInterface::class);
    $serviceRepo->shouldReceive('findById')->andReturn($service);

    $bookingRepo = mock(BookingRepositoryInterface::class);
    $bookingRepo->shouldReceive('sumActiveQuantityOverlapping')->andReturn(3);

    $strategy = new QuantityAvailabilityStrategy($serviceRepo, $bookingRepo);
    $result = $strategy->check(
        $serviceId,
        ['check_in' => '2026-05-01', 'check_out' => '2026-05-03', 'requested' => 2],
    );

    expect($result->available)->toBeTrue();
    expect($result->details)->toEqual([
        'total' => 10,
        'booked' => 3,
        'available' => 7,
        'requested' => 2,
    ]);
});

it('Quantity strategy reports unavailable when requested > available', function (): void {
    $serviceId = ServiceId::generate();
    $service = Service::createQuantity(
        id: $serviceId,
        name: 'Room',
        description: 'Hotel room',
        price: Money::fromCents(100000, 'RUB'),
        totalQuantity: 10,
        categoryId: CategoryId::generate(),
        subcategoryId: null,
        organizationId: OrganizationId::generate(),
    );

    $serviceRepo = mock(ServiceRepositoryInterface::class);
    $serviceRepo->shouldReceive('findById')->andReturn($service);

    $bookingRepo = mock(BookingRepositoryInterface::class);
    $bookingRepo->shouldReceive('sumActiveQuantityOverlapping')->andReturn(9);

    $result = (new QuantityAvailabilityStrategy($serviceRepo, $bookingRepo))->check(
        $serviceId,
        ['check_in' => '2026-05-01', 'check_out' => '2026-05-03', 'requested' => 2],
    );

    expect($result->available)->toBeFalse();
    expect($result->details['available'])->toBe(1);
});

it('AvailabilityChecker dispatches on ServiceType::TIME_SLOT', function (): void {
    $serviceId = ServiceId::generate();
    $tsRepo = mock(TimeSlotRepositoryInterface::class);
    $tsRepo->shouldReceive('findAvailableByServiceAndDate')->andReturn([]);
    $bookingRepo = mock(BookingRepositoryInterface::class);
    $svcRepo = mock(ServiceRepositoryInterface::class);

    $checker = new AvailabilityChecker(
        new TimeSlotAvailabilityStrategy($tsRepo),
        new QuantityAvailabilityStrategy($svcRepo, $bookingRepo),
    );

    $result = $checker->check(ServiceType::TIME_SLOT, $serviceId, ['date' => '2026-05-01']);

    expect($result->details)->toHaveKey('slots');
});

it('AvailabilityChecker dispatches on ServiceType::QUANTITY', function (): void {
    $serviceId = ServiceId::generate();
    $service = Service::createQuantity(
        id: $serviceId,
        name: 'Room',
        description: 'Hotel room',
        price: Money::fromCents(100000, 'RUB'),
        totalQuantity: 5,
        categoryId: CategoryId::generate(),
        subcategoryId: null,
        organizationId: OrganizationId::generate(),
    );

    $svcRepo = mock(ServiceRepositoryInterface::class);
    $svcRepo->shouldReceive('findById')->andReturn($service);
    $bookingRepo = mock(BookingRepositoryInterface::class);
    $bookingRepo->shouldReceive('sumActiveQuantityOverlapping')->andReturn(0);
    $tsRepo = mock(TimeSlotRepositoryInterface::class);

    $checker = new AvailabilityChecker(
        new TimeSlotAvailabilityStrategy($tsRepo),
        new QuantityAvailabilityStrategy($svcRepo, $bookingRepo),
    );

    $result = $checker->check(
        ServiceType::QUANTITY,
        $serviceId,
        ['check_in' => '2026-05-01', 'check_out' => '2026-05-02', 'requested' => 1],
    );

    expect($result->details)->toHaveKey('total');
    expect($result->details['total'])->toBe(5);
});
