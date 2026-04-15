<?php

declare(strict_types=1);

use App\Modules\Catalog\Domain\Entity\Service;
use App\Modules\Catalog\Domain\Specification\ServiceIsActive;
use App\Modules\Catalog\Domain\ValueObject\CategoryId;
use App\Modules\Catalog\Domain\ValueObject\Duration;
use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;

function buildActiveService(): Service
{
    return Service::createTimeSlot(
        id: ServiceId::generate(),
        name: 'Haircut',
        description: 'Premium haircut service',
        price: Money::fromRubles(1500.0),
        duration: Duration::ofMinutes(60),
        categoryId: CategoryId::generate(),
        subcategoryId: null,
    );
}

it('is satisfied by active service', function (): void {
    $spec = new ServiceIsActive;

    expect($spec->isSatisfiedBy(buildActiveService()))->toBeTrue();
});

it('is not satisfied by deactivated service', function (): void {
    $spec = new ServiceIsActive;
    $service = buildActiveService();
    $service->deactivate();

    expect($spec->isSatisfiedBy($service))->toBeFalse();
});
