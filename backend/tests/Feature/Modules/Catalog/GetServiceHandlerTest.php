<?php

declare(strict_types=1);

use App\Modules\Catalog\Application\Query\GetService\GetServiceHandler;
use App\Modules\Catalog\Application\Query\GetService\GetServiceQuery;
use App\Modules\Catalog\Domain\Entity\Service;
use App\Modules\Catalog\Domain\Exception\ServiceNotFoundException;
use App\Modules\Catalog\Domain\Repository\ServiceRepositoryInterface;
use App\Modules\Catalog\Domain\ValueObject\Duration;
use App\Modules\Catalog\Domain\ValueObject\ImagePath;
use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns ServiceDTO with full data and images', function (): void {
    $categoryId = insertCategory('Beauty');
    $subcategoryId = insertSubcategory($categoryId, 'Hair');
    $service = Service::createTimeSlot(
        ServiceId::generate(),
        'Premium Haircut',
        'Full description here',
        Money::fromCents(250000, 'RUB'),
        Duration::ofMinutes(90),
        $categoryId,
        $subcategoryId,
        insertOrganizationForTests(),
    );
    $service->addImage(ImagePath::fromString('services/first.jpg'));
    $service->addImage(ImagePath::fromString('services/second.jpg'));
    app(ServiceRepositoryInterface::class)->save($service);

    $dto = app(GetServiceHandler::class)->handle(new GetServiceQuery($service->id()->toString()));

    expect($dto->id)->toBe($service->id()->toString());
    expect($dto->name)->toBe('Premium Haircut');
    expect($dto->description)->toBe('Full description here');
    expect($dto->priceAmount)->toBe(250000);
    expect($dto->priceCurrency)->toBe('RUB');
    expect($dto->type)->toBe('time_slot');
    expect($dto->durationMinutes)->toBe(90);
    expect($dto->totalQuantity)->toBeNull();
    expect($dto->categoryId)->toBe($categoryId->toString());
    expect($dto->categoryName)->toBe('Beauty');
    expect($dto->subcategoryId)->toBe($subcategoryId->toString());
    expect($dto->subcategoryName)->toBe('Hair');
    expect($dto->isActive)->toBeTrue();
    expect($dto->images)->toBe(['services/first.jpg', 'services/second.jpg']);
    expect($dto->createdAt)->toBeString();
    expect($dto->updatedAt)->toBeString();
});

it('throws ServiceNotFoundException when service missing', function (): void {
    $missing = ServiceId::generate();

    expect(fn () => app(GetServiceHandler::class)->handle(new GetServiceQuery($missing->toString())))
        ->toThrow(ServiceNotFoundException::class);
});

it('returns quantity service with totalQuantity and null duration', function (): void {
    $categoryId = insertCategory('Restaurant');
    $service = Service::createQuantity(
        ServiceId::generate(),
        'Table',
        'Tables for 4',
        Money::fromCents(500000, 'RUB'),
        10,
        $categoryId,
        null,
        insertOrganizationForTests(),
    );
    app(ServiceRepositoryInterface::class)->save($service);

    $dto = app(GetServiceHandler::class)->handle(new GetServiceQuery($service->id()->toString()));

    expect($dto->type)->toBe('quantity');
    expect($dto->totalQuantity)->toBe(10);
    expect($dto->durationMinutes)->toBeNull();
    expect($dto->subcategoryId)->toBeNull();
    expect($dto->subcategoryName)->toBeNull();
    expect($dto->images)->toBe([]);
});
