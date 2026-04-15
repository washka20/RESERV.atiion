<?php

declare(strict_types=1);

use App\Modules\Catalog\Domain\Entity\Service;
use App\Modules\Catalog\Domain\Specification\ServiceHasSufficientInfo;
use App\Modules\Catalog\Domain\ValueObject\CategoryId;
use App\Modules\Catalog\Domain\ValueObject\Duration;
use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use App\Modules\Catalog\Domain\ValueObject\ServiceType;

function buildService(string $name = 'Haircut', string $description = 'Premium haircut service with styling', int $priceCents = 150000): Service
{
    return Service::restore(
        id: ServiceId::generate(),
        name: $name,
        description: $description,
        price: Money::fromCents($priceCents),
        type: ServiceType::TIME_SLOT,
        duration: Duration::ofMinutes(60),
        totalQuantity: null,
        categoryId: CategoryId::generate(),
        subcategoryId: null,
        isActive: true,
        images: [],
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );
}

it('is satisfied by fully populated service', function (): void {
    $spec = new ServiceHasSufficientInfo;

    expect($spec->isSatisfiedBy(buildService()))->toBeTrue();
});

it('is not satisfied by empty name', function (): void {
    $spec = new ServiceHasSufficientInfo;

    expect($spec->isSatisfiedBy(buildService(name: '')))->toBeFalse();
});

it('is not satisfied by zero price', function (): void {
    $spec = new ServiceHasSufficientInfo;

    expect($spec->isSatisfiedBy(buildService(priceCents: 0)))->toBeFalse();
});

it('is not satisfied by short description', function (): void {
    $spec = new ServiceHasSufficientInfo;

    expect($spec->isSatisfiedBy(buildService(description: 'short')))->toBeFalse();
});
