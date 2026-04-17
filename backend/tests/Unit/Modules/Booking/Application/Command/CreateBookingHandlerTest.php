<?php

declare(strict_types=1);

use App\Modules\Booking\Application\Command\CreateBooking\CreateBookingCommand;
use App\Modules\Booking\Application\Command\CreateBooking\CreateBookingHandler;
use App\Modules\Booking\Application\DTO\BookingDTO;
use App\Modules\Booking\Domain\Entity\TimeSlot;
use App\Modules\Booking\Domain\Exception\InsufficientQuantityException;
use App\Modules\Booking\Domain\Exception\SlotUnavailableException;
use App\Modules\Booking\Domain\Repository\BookingRepositoryInterface;
use App\Modules\Booking\Domain\Repository\TimeSlotRepositoryInterface;
use App\Modules\Booking\Domain\Specification\BookingPolicy;
use App\Modules\Booking\Domain\ValueObject\SlotId;
use App\Modules\Catalog\Domain\Entity\Service;
use App\Modules\Catalog\Domain\Repository\ServiceRepositoryInterface;
use App\Modules\Catalog\Domain\ValueObject\CategoryId;
use App\Modules\Catalog\Domain\ValueObject\Duration;
use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use App\Modules\Identity\Domain\ValueObject\UserId;
use App\Shared\Application\Event\DomainEventDispatcherInterface;
use App\Shared\Application\Transaction\TransactionManagerInterface;

function passthroughTx(): TransactionManagerInterface
{
    $tx = mock(TransactionManagerInterface::class);
    $tx->shouldReceive('transactional')->andReturnUsing(fn (callable $cb) => $cb());

    return $tx;
}

function makeTimeSlotServiceForCreate(ServiceId $id): Service
{
    return Service::createTimeSlot(
        id: $id,
        name: 'Haircut',
        description: 'Premium',
        price: Money::fromCents(150000, 'RUB'),
        duration: Duration::ofMinutes(60),
        categoryId: CategoryId::generate(),
        subcategoryId: null,
    );
}

function makeQuantityServiceForCreate(ServiceId $id, int $total = 10): Service
{
    return Service::createQuantity(
        id: $id,
        name: 'Room',
        description: 'Hotel room',
        price: Money::fromCents(500000, 'RUB'),
        totalQuantity: $total,
        categoryId: CategoryId::generate(),
        subcategoryId: null,
    );
}

it('creates a TIME_SLOT booking via happy path', function (): void {
    $serviceId = ServiceId::generate();
    $slotId = SlotId::generate();
    $slot = TimeSlot::create(
        $slotId,
        $serviceId,
        new DateTimeImmutable('+2 days 10:00'),
        new DateTimeImmutable('+2 days 11:00'),
    );
    $service = makeTimeSlotServiceForCreate($serviceId);

    $serviceRepo = mock(ServiceRepositoryInterface::class);
    $serviceRepo->shouldReceive('findById')->andReturn($service);

    $bookingRepo = mock(BookingRepositoryInterface::class);
    $bookingRepo->shouldReceive('countActiveByUserId')->andReturn(0);
    $bookingRepo->shouldReceive('save')->once();

    $slotRepo = mock(TimeSlotRepositoryInterface::class);
    $slotRepo->shouldReceive('findById')->andReturn($slot);
    $slotRepo->shouldReceive('markAsBooked')->once()->andReturnTrue();

    $policy = mock(BookingPolicy::class);
    $policy->shouldReceive('isSatisfiedByWithContext')->andReturnTrue();

    $dispatcher = mock(DomainEventDispatcherInterface::class);
    $dispatcher->shouldReceive('dispatchAll')->once();

    $handler = new CreateBookingHandler($serviceRepo, $bookingRepo, $slotRepo, $policy, $dispatcher, passthroughTx());
    $dto = $handler->handle(new CreateBookingCommand(
        userId: UserId::generate()->toString(),
        serviceId: $serviceId->toString(),
        slotId: $slotId->toString(),
        notes: 'Please arrive early',
    ));

    expect($dto)->toBeInstanceOf(BookingDTO::class);
    expect($dto->type)->toBe('time_slot');
    expect($dto->status)->toBe('pending');
});

it('fails TIME_SLOT booking when markAsBooked returns false (race condition)', function (): void {
    $serviceId = ServiceId::generate();
    $slotId = SlotId::generate();
    $slot = TimeSlot::create(
        $slotId,
        $serviceId,
        new DateTimeImmutable('+2 days 10:00'),
        new DateTimeImmutable('+2 days 11:00'),
    );
    $service = makeTimeSlotServiceForCreate($serviceId);

    $serviceRepo = mock(ServiceRepositoryInterface::class);
    $serviceRepo->shouldReceive('findById')->andReturn($service);

    $bookingRepo = mock(BookingRepositoryInterface::class);
    $bookingRepo->shouldReceive('countActiveByUserId')->andReturn(0);
    $bookingRepo->shouldReceive('save')->once();

    $slotRepo = mock(TimeSlotRepositoryInterface::class);
    $slotRepo->shouldReceive('findById')->andReturn($slot);
    $slotRepo->shouldReceive('markAsBooked')->once()->andReturnFalse();

    $policy = mock(BookingPolicy::class);
    $policy->shouldReceive('isSatisfiedByWithContext')->andReturnTrue();

    $dispatcher = mock(DomainEventDispatcherInterface::class);

    $handler = new CreateBookingHandler($serviceRepo, $bookingRepo, $slotRepo, $policy, $dispatcher, passthroughTx());
    $handler->handle(new CreateBookingCommand(
        userId: UserId::generate()->toString(),
        serviceId: $serviceId->toString(),
        slotId: $slotId->toString(),
    ));
})->throws(SlotUnavailableException::class);

it('creates a QUANTITY booking via happy path', function (): void {
    $serviceId = ServiceId::generate();
    $service = makeQuantityServiceForCreate($serviceId, total: 10);

    $serviceRepo = mock(ServiceRepositoryInterface::class);
    $serviceRepo->shouldReceive('findById')->andReturn($service);

    $bookingRepo = mock(BookingRepositoryInterface::class);
    $bookingRepo->shouldReceive('countActiveByUserId')->andReturn(0);
    $bookingRepo->shouldReceive('sumActiveQuantityOverlapping')->andReturn(3);
    $bookingRepo->shouldReceive('save')->once();

    $slotRepo = mock(TimeSlotRepositoryInterface::class);

    $policy = mock(BookingPolicy::class);
    $policy->shouldReceive('isSatisfiedByWithContext')->andReturnTrue();

    $dispatcher = mock(DomainEventDispatcherInterface::class);
    $dispatcher->shouldReceive('dispatchAll')->once();

    $handler = new CreateBookingHandler($serviceRepo, $bookingRepo, $slotRepo, $policy, $dispatcher, passthroughTx());
    $dto = $handler->handle(new CreateBookingCommand(
        userId: UserId::generate()->toString(),
        serviceId: $serviceId->toString(),
        checkIn: '2026-05-01',
        checkOut: '2026-05-04',
        quantity: 2,
    ));

    expect($dto->type)->toBe('quantity');
    expect($dto->quantity)->toBe(2);
    expect($dto->totalPriceAmount)->toBe(500000 * 2 * 3);
});

it('fails QUANTITY booking when insufficient quantity', function (): void {
    $serviceId = ServiceId::generate();
    $service = makeQuantityServiceForCreate($serviceId, total: 10);

    $serviceRepo = mock(ServiceRepositoryInterface::class);
    $serviceRepo->shouldReceive('findById')->andReturn($service);

    $bookingRepo = mock(BookingRepositoryInterface::class);
    $bookingRepo->shouldReceive('countActiveByUserId')->andReturn(0);
    $bookingRepo->shouldReceive('sumActiveQuantityOverlapping')->andReturn(9);

    $slotRepo = mock(TimeSlotRepositoryInterface::class);
    $policy = mock(BookingPolicy::class);
    $dispatcher = mock(DomainEventDispatcherInterface::class);

    $handler = new CreateBookingHandler($serviceRepo, $bookingRepo, $slotRepo, $policy, $dispatcher, passthroughTx());
    $handler->handle(new CreateBookingCommand(
        userId: UserId::generate()->toString(),
        serviceId: $serviceId->toString(),
        checkIn: '2026-05-01',
        checkOut: '2026-05-04',
        quantity: 2,
    ));
})->throws(InsufficientQuantityException::class);
