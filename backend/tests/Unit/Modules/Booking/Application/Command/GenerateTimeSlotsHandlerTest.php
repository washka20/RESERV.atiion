<?php

declare(strict_types=1);

use App\Modules\Booking\Application\Command\GenerateTimeSlots\GenerateTimeSlotsCommand;
use App\Modules\Booking\Application\Command\GenerateTimeSlots\GenerateTimeSlotsHandler;
use App\Modules\Booking\Domain\Entity\TimeSlot;
use App\Modules\Booking\Domain\Event\TimeSlotGenerated;
use App\Modules\Booking\Domain\Repository\TimeSlotRepositoryInterface;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use App\Shared\Application\Event\DomainEventDispatcherInterface;

it('generates 6 slots across 2 days with 10:00-13:00 window and 60min duration', function (): void {
    $serviceId = ServiceId::generate();
    $capturedSlots = null;

    $slotRepo = mock(TimeSlotRepositoryInterface::class);
    $slotRepo->shouldReceive('saveMany')
        ->once()
        ->with(Mockery::on(function (array $slots) use (&$capturedSlots): bool {
            $capturedSlots = $slots;

            return true;
        }));

    $dispatcher = mock(DomainEventDispatcherInterface::class);
    $dispatcher->shouldReceive('dispatch')->once()->with(Mockery::type(TimeSlotGenerated::class));

    $handler = new GenerateTimeSlotsHandler($slotRepo, $dispatcher);
    $count = $handler->handle(new GenerateTimeSlotsCommand(
        serviceId: $serviceId->toString(),
        dateFrom: '2026-05-04',
        dateTo: '2026-05-05',
        timeFrom: '10:00',
        timeTo: '13:00',
        slotDurationMinutes: 60,
        breakMinutes: 0,
    ));

    expect($count)->toBe(6);
    expect($capturedSlots)->toHaveCount(6);
    expect($capturedSlots[0])->toBeInstanceOf(TimeSlot::class);
    expect($capturedSlots[0]->range->startAt->format('Y-m-d H:i'))->toBe('2026-05-04 10:00');
    expect($capturedSlots[0]->range->endAt->format('Y-m-d H:i'))->toBe('2026-05-04 11:00');
    expect($capturedSlots[2]->range->startAt->format('Y-m-d H:i'))->toBe('2026-05-04 12:00');
    expect($capturedSlots[3]->range->startAt->format('Y-m-d H:i'))->toBe('2026-05-05 10:00');
    expect($capturedSlots[5]->range->endAt->format('Y-m-d H:i'))->toBe('2026-05-05 13:00');
});

it('excludes specific weekdays from generation', function (): void {
    $serviceId = ServiceId::generate();
    $capturedSlots = null;

    $slotRepo = mock(TimeSlotRepositoryInterface::class);
    $slotRepo->shouldReceive('saveMany')
        ->once()
        ->with(Mockery::on(function (array $slots) use (&$capturedSlots): bool {
            $capturedSlots = $slots;

            return true;
        }));

    $dispatcher = mock(DomainEventDispatcherInterface::class);
    $dispatcher->shouldReceive('dispatch')->once();

    $handler = new GenerateTimeSlotsHandler($slotRepo, $dispatcher);
    $count = $handler->handle(new GenerateTimeSlotsCommand(
        serviceId: $serviceId->toString(),
        dateFrom: '2026-05-01',
        dateTo: '2026-05-04',
        timeFrom: '10:00',
        timeTo: '11:00',
        slotDurationMinutes: 60,
        breakMinutes: 0,
        excludeDaysOfWeek: [0],
    ));

    expect($count)->toBe(3);

    $dates = array_map(
        fn (TimeSlot $slot): string => $slot->range->startAt->format('Y-m-d'),
        $capturedSlots,
    );

    expect($dates)->toBe(['2026-05-01', '2026-05-02', '2026-05-04']);
    expect($dates)->not->toContain('2026-05-03');
});

it('respects breaks between slots', function (): void {
    $serviceId = ServiceId::generate();
    $capturedSlots = null;

    $slotRepo = mock(TimeSlotRepositoryInterface::class);
    $slotRepo->shouldReceive('saveMany')
        ->once()
        ->with(Mockery::on(function (array $slots) use (&$capturedSlots): bool {
            $capturedSlots = $slots;

            return true;
        }));

    $dispatcher = mock(DomainEventDispatcherInterface::class);
    $dispatcher->shouldReceive('dispatch')->once();

    $handler = new GenerateTimeSlotsHandler($slotRepo, $dispatcher);
    $count = $handler->handle(new GenerateTimeSlotsCommand(
        serviceId: $serviceId->toString(),
        dateFrom: '2026-05-04',
        dateTo: '2026-05-04',
        timeFrom: '10:00',
        timeTo: '14:00',
        slotDurationMinutes: 60,
        breakMinutes: 30,
    ));

    expect($count)->toBe(3);

    $windows = array_map(
        fn (TimeSlot $slot): string => $slot->range->startAt->format('H:i').'-'.$slot->range->endAt->format('H:i'),
        $capturedSlots,
    );

    expect($windows)->toBe(['10:00-11:00', '11:30-12:30', '13:00-14:00']);
});

it('calls saveMany exactly once with all generated slots', function (): void {
    $serviceId = ServiceId::generate();

    $slotRepo = mock(TimeSlotRepositoryInterface::class);
    $slotRepo->shouldReceive('saveMany')
        ->once()
        ->with(Mockery::on(fn (array $slots): bool => count($slots) === 4));

    $dispatcher = mock(DomainEventDispatcherInterface::class);
    $dispatcher->shouldReceive('dispatch')->once();

    $handler = new GenerateTimeSlotsHandler($slotRepo, $dispatcher);
    $count = $handler->handle(new GenerateTimeSlotsCommand(
        serviceId: $serviceId->toString(),
        dateFrom: '2026-05-04',
        dateTo: '2026-05-04',
        timeFrom: '09:00',
        timeTo: '13:00',
        slotDurationMinutes: 60,
        breakMinutes: 0,
    ));

    expect($count)->toBe(4);
});
