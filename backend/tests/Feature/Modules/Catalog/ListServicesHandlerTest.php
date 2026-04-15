<?php

declare(strict_types=1);

use App\Modules\Catalog\Application\Query\ListServices\ListServicesQuery;
use App\Modules\Catalog\Domain\Entity\Service;
use App\Modules\Catalog\Domain\Repository\ServiceRepositoryInterface;
use App\Modules\Catalog\Domain\ValueObject\ImagePath;
use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns all active services by default', function (): void {
    $catId = insertCategory();
    saveTimeSlotService('A', $catId);
    saveTimeSlotService('B', $catId);
    $inactive = saveTimeSlotService('C', $catId);
    $inactive->deactivate();
    app(ServiceRepositoryInterface::class)->save($inactive);

    $result = listServicesHandler()->handle(new ListServicesQuery);

    expect($result->total)->toBe(2);
    expect($result->data)->toHaveCount(2);
    $names = array_map(static fn ($d) => $d->name, $result->data);
    sort($names);
    expect($names)->toBe(['A', 'B']);
});

it('includes inactive when isActive=null', function (): void {
    $catId = insertCategory();
    saveTimeSlotService('Active', $catId);
    $inactive = saveTimeSlotService('Inactive', $catId);
    $inactive->deactivate();
    app(ServiceRepositoryInterface::class)->save($inactive);

    $result = listServicesHandler()->handle(new ListServicesQuery(isActive: null));

    expect($result->total)->toBe(2);
});

it('filters by categoryId', function (): void {
    $catA = insertCategory('CatA');
    $catB = insertCategory('CatB');
    saveTimeSlotService('A1', $catA);
    saveTimeSlotService('A2', $catA);
    saveTimeSlotService('B1', $catB);

    $result = listServicesHandler()->handle(new ListServicesQuery(categoryId: $catA->toString()));

    expect($result->total)->toBe(2);
    $names = array_map(static fn ($d) => $d->name, $result->data);
    sort($names);
    expect($names)->toBe(['A1', 'A2']);
});

it('filters by type quantity', function (): void {
    $catId = insertCategory();
    saveTimeSlotService('TimeSlotOne', $catId);
    $qtyService = Service::createQuantity(
        ServiceId::generate(),
        'QtyOne',
        'desc',
        Money::fromCents(100000, 'RUB'),
        5,
        $catId,
        null,
    );
    app(ServiceRepositoryInterface::class)->save($qtyService);

    $result = listServicesHandler()->handle(new ListServicesQuery(type: 'quantity'));

    expect($result->total)->toBe(1);
    expect($result->data[0]->name)->toBe('QtyOne');
    expect($result->data[0]->type)->toBe('quantity');
});

it('filters by search (case-insensitive)', function (): void {
    $catId = insertCategory();
    saveTimeSlotService('Haircut Premium', $catId);
    saveTimeSlotService('Manicure', $catId);
    saveTimeSlotService('Pedicure', $catId, description: 'Best haircut');

    $result = listServicesHandler()->handle(new ListServicesQuery(search: 'haircut'));

    expect($result->total)->toBe(2);
});

it('paginates results', function (): void {
    $catId = insertCategory();
    saveTimeSlotService('S1', $catId);
    saveTimeSlotService('S2', $catId);
    saveTimeSlotService('S3', $catId);

    $page1 = listServicesHandler()->handle(new ListServicesQuery(page: 1, perPage: 2));
    $page2 = listServicesHandler()->handle(new ListServicesQuery(page: 2, perPage: 2));

    expect($page1->total)->toBe(3);
    expect($page1->data)->toHaveCount(2);
    expect($page2->total)->toBe(3);
    expect($page2->data)->toHaveCount(1);
});

it('uses primary image with lowest sort_order', function (): void {
    $catId = insertCategory();
    $service = saveTimeSlotService('WithImages', $catId);
    $service->addImage(ImagePath::fromString('services/a.jpg'));
    $service->addImage(ImagePath::fromString('services/b.jpg'));
    app(ServiceRepositoryInterface::class)->save($service);

    $result = listServicesHandler()->handle(new ListServicesQuery);

    expect($result->data[0]->primaryImage)->toBe('services/a.jpg');
});

it('filters by min/max price', function (): void {
    $catId = insertCategory();
    saveTimeSlotService('Cheap', $catId, priceCents: 50000);
    saveTimeSlotService('Mid', $catId, priceCents: 150000);
    saveTimeSlotService('Expensive', $catId, priceCents: 500000);

    $midRange = listServicesHandler()->handle(new ListServicesQuery(minPrice: 100000, maxPrice: 200000));

    expect($midRange->total)->toBe(1);
    expect($midRange->data[0]->name)->toBe('Mid');
});

it('filters by subcategoryId', function (): void {
    $catId = insertCategory();
    $subA = insertSubcategory($catId, 'Hair');
    $subB = insertSubcategory($catId, 'Nails');
    saveTimeSlotService('HairCut', $catId, subcategoryId: $subA);
    saveTimeSlotService('HairColor', $catId, subcategoryId: $subA);
    saveTimeSlotService('Manicure', $catId, subcategoryId: $subB);

    $result = listServicesHandler()->handle(new ListServicesQuery(subcategoryId: $subA->toString()));

    expect($result->total)->toBe(2);
});

it('returns categoryName in list items', function (): void {
    $catId = insertCategory('Beauty');
    saveTimeSlotService('Haircut', $catId);

    $result = listServicesHandler()->handle(new ListServicesQuery);

    expect($result->data[0]->categoryName)->toBe('Beauty');
});
