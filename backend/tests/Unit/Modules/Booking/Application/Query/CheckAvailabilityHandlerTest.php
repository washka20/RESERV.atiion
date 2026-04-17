<?php

declare(strict_types=1);

use App\Modules\Booking\Application\DTO\QuantityAvailabilityDTO;
use App\Modules\Booking\Application\DTO\TimeSlotAvailabilityDTO;
use App\Modules\Booking\Application\Query\CheckAvailability\CheckAvailabilityHandler;
use App\Modules\Booking\Application\Query\CheckAvailability\CheckAvailabilityQuery;
use App\Modules\Booking\Domain\Entity\TimeSlot;
use App\Modules\Booking\Domain\Service\AvailabilityChecker;
use App\Modules\Booking\Domain\Service\AvailabilityResult;
use App\Modules\Booking\Domain\ValueObject\SlotId;
use App\Modules\Catalog\Domain\Entity\Service;
use App\Modules\Catalog\Domain\Exception\ServiceNotFoundException;
use App\Modules\Catalog\Domain\Repository\ServiceRepositoryInterface;
use App\Modules\Catalog\Domain\ValueObject\CategoryId;
use App\Modules\Catalog\Domain\ValueObject\Duration;
use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;

function makeTimeSlotServiceForAvailability(ServiceId $id): Service
{
    return Service::createTimeSlot(
        id: $id,
        name: 'Haircut',
        description: 'desc',
        price: Money::fromCents(100000, 'RUB'),
        duration: Duration::ofMinutes(60),
        categoryId: CategoryId::generate(),
        subcategoryId: null,
    );
}

function makeQuantityServiceForAvailability(ServiceId $id, int $total = 10): Service
{
    return Service::createQuantity(
        id: $id,
        name: 'Room',
        description: 'desc',
        price: Money::fromCents(500000, 'RUB'),
        totalQuantity: $total,
        categoryId: CategoryId::generate(),
        subcategoryId: null,
    );
}

it('returns TimeSlotAvailabilityDTO for TIME_SLOT service', function (): void {
    $serviceId = ServiceId::generate();
    $service = makeTimeSlotServiceForAvailability($serviceId);

    $slot = TimeSlot::create(
        SlotId::generate(),
        $serviceId,
        new DateTimeImmutable('+1 day 10:00'),
        new DateTimeImmutable('+1 day 11:00'),
    );

    $serviceRepo = mock(ServiceRepositoryInterface::class);
    $serviceRepo->shouldReceive('findById')->andReturn($service);

    $checker = mock(AvailabilityChecker::class);
    $checker->shouldReceive('check')
        ->once()
        ->andReturn(new AvailabilityResult(available: true, details: ['slots' => [$slot]]));

    $handler = new CheckAvailabilityHandler($serviceRepo, $checker);
    $dto = $handler->handle(new CheckAvailabilityQuery(
        serviceId: $serviceId->toString(),
        params: ['date' => (new DateTimeImmutable('+1 day'))->format('Y-m-d')],
    ));

    expect($dto)->toBeInstanceOf(TimeSlotAvailabilityDTO::class)
        ->and($dto->type)->toBe('time_slot')
        ->and($dto->available)->toBeTrue()
        ->and($dto->slots)->toHaveCount(1)
        ->and($dto->slots[0]['id'])->toBe($slot->id->toString());
});

it('returns QuantityAvailabilityDTO for QUANTITY service', function (): void {
    $serviceId = ServiceId::generate();
    $service = makeQuantityServiceForAvailability($serviceId, total: 10);

    $serviceRepo = mock(ServiceRepositoryInterface::class);
    $serviceRepo->shouldReceive('findById')->andReturn($service);

    $checker = mock(AvailabilityChecker::class);
    $checker->shouldReceive('check')
        ->once()
        ->andReturn(new AvailabilityResult(
            available: true,
            details: ['total' => 10, 'booked' => 3, 'available' => 7, 'requested' => 2],
        ));

    $handler = new CheckAvailabilityHandler($serviceRepo, $checker);
    $dto = $handler->handle(new CheckAvailabilityQuery(
        serviceId: $serviceId->toString(),
        params: [
            'check_in' => (new DateTimeImmutable('+1 day'))->format('Y-m-d'),
            'check_out' => (new DateTimeImmutable('+2 days'))->format('Y-m-d'),
            'requested' => 2,
        ],
    ));

    expect($dto)->toBeInstanceOf(QuantityAvailabilityDTO::class)
        ->and($dto->type)->toBe('quantity')
        ->and($dto->available)->toBeTrue()
        ->and($dto->total)->toBe(10)
        ->and($dto->booked)->toBe(3)
        ->and($dto->availableQuantity)->toBe(7)
        ->and($dto->requested)->toBe(2);
});

it('throws ServiceNotFoundException when service is missing', function (): void {
    $serviceRepo = mock(ServiceRepositoryInterface::class);
    $serviceRepo->shouldReceive('findById')->andReturnNull();

    $checker = mock(AvailabilityChecker::class);

    $handler = new CheckAvailabilityHandler($serviceRepo, $checker);
    $handler->handle(new CheckAvailabilityQuery(
        serviceId: ServiceId::generate()->toString(),
        params: [],
    ));
})->throws(ServiceNotFoundException::class);
