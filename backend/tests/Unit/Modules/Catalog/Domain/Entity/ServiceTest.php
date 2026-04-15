<?php

declare(strict_types=1);

use App\Modules\Catalog\Domain\Entity\Service;
use App\Modules\Catalog\Domain\Event\ServiceActivated;
use App\Modules\Catalog\Domain\Event\ServiceCreated;
use App\Modules\Catalog\Domain\Event\ServiceDeactivated;
use App\Modules\Catalog\Domain\Event\ServiceUpdated;
use App\Modules\Catalog\Domain\Exception\InvalidServiceTypeException;
use App\Modules\Catalog\Domain\ValueObject\CategoryId;
use App\Modules\Catalog\Domain\ValueObject\Duration;
use App\Modules\Catalog\Domain\ValueObject\ImagePath;
use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use App\Modules\Catalog\Domain\ValueObject\ServiceType;
use App\Modules\Catalog\Domain\ValueObject\SubcategoryId;

function makeTimeSlotService(): Service
{
    return Service::createTimeSlot(
        id: ServiceId::generate(),
        name: 'Haircut',
        description: 'Premium haircut service with styling',
        price: Money::fromRubles(1500.0),
        duration: Duration::ofMinutes(60),
        categoryId: CategoryId::generate(),
        subcategoryId: null,
    );
}

function makeQuantityService(): Service
{
    return Service::createQuantity(
        id: ServiceId::generate(),
        name: 'Table Rental',
        description: 'Rent a table for a private event',
        price: Money::fromRubles(500.0),
        totalQuantity: 10,
        categoryId: CategoryId::generate(),
        subcategoryId: SubcategoryId::generate(),
    );
}

it('creates a TIME_SLOT service with duration', function (): void {
    $service = makeTimeSlotService();

    expect($service->type())->toBe(ServiceType::TIME_SLOT)
        ->and($service->duration()?->minutes())->toBe(60)
        ->and($service->totalQuantity())->toBeNull()
        ->and($service->isActive())->toBeTrue()
        ->and($service->images())->toBe([]);
});

it('creates a QUANTITY service with totalQuantity', function (): void {
    $service = makeQuantityService();

    expect($service->type())->toBe(ServiceType::QUANTITY)
        ->and($service->totalQuantity())->toBe(10)
        ->and($service->duration())->toBeNull()
        ->and($service->isActive())->toBeTrue();
});

it('emits ServiceCreated event on creation', function (): void {
    $service = makeTimeSlotService();

    $events = $service->pullDomainEvents();

    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(ServiceCreated::class);
});

it('pullDomainEvents clears the events list', function (): void {
    $service = makeTimeSlotService();

    $service->pullDomainEvents();

    expect($service->pullDomainEvents())->toBe([]);
});

it('throws when TIME_SLOT created with zero-like duration via QUANTITY factory', function (): void {
    Service::createQuantity(
        id: ServiceId::generate(),
        name: 'x',
        description: 'short desc here',
        price: Money::fromRubles(100.0),
        totalQuantity: 0,
        categoryId: CategoryId::generate(),
        subcategoryId: null,
    );
})->throws(InvalidServiceTypeException::class);

it('updateDetails changes name/description/price and emits ServiceUpdated', function (): void {
    $service = makeTimeSlotService();
    $service->pullDomainEvents();

    $service->updateDetails(
        name: 'New Haircut',
        description: 'Updated description for haircut service',
        price: Money::fromRubles(2000.0),
    );

    expect($service->name())->toBe('New Haircut')
        ->and($service->description())->toBe('Updated description for haircut service')
        ->and($service->price()->amount())->toBe(200000);

    $events = $service->pullDomainEvents();
    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(ServiceUpdated::class);
});

it('deactivate on active service emits ServiceDeactivated', function (): void {
    $service = makeTimeSlotService();
    $service->pullDomainEvents();

    $service->deactivate();

    expect($service->isActive())->toBeFalse();
    $events = $service->pullDomainEvents();
    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(ServiceDeactivated::class);
});

it('deactivate is idempotent on already inactive service', function (): void {
    $service = makeTimeSlotService();
    $service->deactivate();
    $service->pullDomainEvents();

    $service->deactivate();

    expect($service->isActive())->toBeFalse()
        ->and($service->pullDomainEvents())->toBe([]);
});

it('activate on inactive service emits ServiceActivated', function (): void {
    $service = makeTimeSlotService();
    $service->deactivate();
    $service->pullDomainEvents();

    $service->activate();

    expect($service->isActive())->toBeTrue();
    $events = $service->pullDomainEvents();
    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(ServiceActivated::class);
});

it('activate is idempotent on already active service', function (): void {
    $service = makeTimeSlotService();
    $service->pullDomainEvents();

    $service->activate();

    expect($service->pullDomainEvents())->toBe([]);
});

it('addImage appends to images list', function (): void {
    $service = makeTimeSlotService();
    $path = ImagePath::fromString('services/abc.jpg');

    $service->addImage($path);

    expect($service->images())->toHaveCount(1)
        ->and($service->images()[0]->equals($path))->toBeTrue();
});

it('addImage is idempotent for the same path', function (): void {
    $service = makeTimeSlotService();
    $path = ImagePath::fromString('services/abc.jpg');

    $service->addImage($path);
    $service->addImage($path);

    expect($service->images())->toHaveCount(1);
});

it('removeImage removes by path', function (): void {
    $service = makeTimeSlotService();
    $path = ImagePath::fromString('services/abc.jpg');
    $service->addImage($path);

    $service->removeImage($path);

    expect($service->images())->toBe([]);
});

it('restore rebuilds service without events', function (): void {
    $id = ServiceId::generate();
    $categoryId = CategoryId::generate();
    $now = new DateTimeImmutable;

    $service = Service::restore(
        id: $id,
        name: 'Restored',
        description: 'Restored description text',
        price: Money::fromRubles(100.0),
        type: ServiceType::TIME_SLOT,
        duration: Duration::ofMinutes(30),
        totalQuantity: null,
        categoryId: $categoryId,
        subcategoryId: null,
        isActive: true,
        images: [],
        createdAt: $now,
        updatedAt: $now,
    );

    expect($service->id()->equals($id))->toBeTrue()
        ->and($service->pullDomainEvents())->toBe([]);
});
