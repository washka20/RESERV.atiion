<?php

declare(strict_types=1);

use App\Modules\Catalog\Domain\Entity\Service;
use App\Modules\Catalog\Domain\Exception\ServiceNotFoundException;
use App\Modules\Catalog\Domain\Repository\ServiceRepositoryInterface;
use App\Modules\Catalog\Domain\ValueObject\CategoryId;
use App\Modules\Catalog\Domain\ValueObject\Duration;
use App\Modules\Catalog\Domain\ValueObject\ImagePath;
use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use App\Modules\Catalog\Domain\ValueObject\SubcategoryId;
use App\Modules\Catalog\Infrastructure\Persistence\Model\CategoryModel;
use App\Modules\Catalog\Infrastructure\Persistence\Model\ServiceImageModel;
use App\Modules\Catalog\Infrastructure\Persistence\Model\SubcategoryModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeCategoryRow(): CategoryId
{
    $id = CategoryId::generate();
    CategoryModel::query()->insert([
        'id' => $id->toString(),
        'name' => 'Beauty',
        'slug' => 'beauty-'.substr($id->toString(), 0, 8),
        'sort_order' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return $id;
}

function makeSubcategoryRow(CategoryId $categoryId): SubcategoryId
{
    $id = SubcategoryId::generate();
    SubcategoryModel::query()->insert([
        'id' => $id->toString(),
        'category_id' => $categoryId->toString(),
        'name' => 'Hair',
        'slug' => 'hair-'.substr($id->toString(), 0, 8),
        'sort_order' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return $id;
}

function repo(): ServiceRepositoryInterface
{
    return app(ServiceRepositoryInterface::class);
}

it('saves and finds a TIME_SLOT service', function (): void {
    $categoryId = makeCategoryRow();
    $service = Service::createTimeSlot(
        ServiceId::generate(),
        'Haircut',
        'Professional haircut',
        Money::fromCents(150000, 'RUB'),
        Duration::ofMinutes(60),
        $categoryId,
        null,
    );

    repo()->save($service);

    $found = repo()->findById($service->id());
    expect($found)->not->toBeNull();
    expect($found->name())->toBe('Haircut');
    expect($found->description())->toBe('Professional haircut');
    expect($found->price()->amount())->toBe(150000);
    expect($found->price()->currency())->toBe('RUB');
    expect($found->duration()?->minutes())->toBe(60);
    expect($found->totalQuantity())->toBeNull();
    expect($found->categoryId()->equals($categoryId))->toBeTrue();
    expect($found->isActive())->toBeTrue();
    expect($found->images())->toBe([]);
});

it('saves and finds a QUANTITY service with subcategory', function (): void {
    $categoryId = makeCategoryRow();
    $subcategoryId = makeSubcategoryRow($categoryId);

    $service = Service::createQuantity(
        ServiceId::generate(),
        'Table',
        'Restaurant table',
        Money::fromCents(500000, 'RUB'),
        10,
        $categoryId,
        $subcategoryId,
    );

    repo()->save($service);

    $found = repo()->findById($service->id());
    expect($found)->not->toBeNull();
    expect($found->totalQuantity())->toBe(10);
    expect($found->duration())->toBeNull();
    expect($found->subcategoryId()?->equals($subcategoryId))->toBeTrue();
});

it('updates existing service on repeated save', function (): void {
    $categoryId = makeCategoryRow();
    $service = Service::createTimeSlot(
        ServiceId::generate(),
        'Haircut',
        'Original description',
        Money::fromCents(100000, 'RUB'),
        Duration::ofMinutes(30),
        $categoryId,
        null,
    );
    repo()->save($service);

    $service->updateDetails('Haircut Premium', 'Updated description', Money::fromCents(200000, 'RUB'));
    repo()->save($service);

    $found = repo()->findById($service->id());
    expect($found->name())->toBe('Haircut Premium');
    expect($found->description())->toBe('Updated description');
    expect($found->price()->amount())->toBe(200000);
});

it('saves service with images and retrieves them in order', function (): void {
    $categoryId = makeCategoryRow();
    $service = Service::createTimeSlot(
        ServiceId::generate(),
        'Service',
        'desc',
        Money::fromCents(1000, 'RUB'),
        Duration::ofMinutes(30),
        $categoryId,
        null,
    );
    $service->addImage(ImagePath::fromString('services/first.jpg'));
    $service->addImage(ImagePath::fromString('services/second.jpg'));
    $service->addImage(ImagePath::fromString('services/third.jpg'));

    repo()->save($service);

    $found = repo()->findById($service->id());
    $paths = array_map(static fn ($p) => $p->value(), $found->images());
    expect($paths)->toBe([
        'services/first.jpg',
        'services/second.jpg',
        'services/third.jpg',
    ]);
});

it('removes deleted images on re-save', function (): void {
    $categoryId = makeCategoryRow();
    $service = Service::createTimeSlot(
        ServiceId::generate(),
        'Service',
        'desc',
        Money::fromCents(1000, 'RUB'),
        Duration::ofMinutes(30),
        $categoryId,
        null,
    );
    $img1 = ImagePath::fromString('services/a.jpg');
    $img2 = ImagePath::fromString('services/b.jpg');
    $service->addImage($img1);
    $service->addImage($img2);
    repo()->save($service);

    $service->removeImage($img1);
    repo()->save($service);

    $found = repo()->findById($service->id());
    $paths = array_map(static fn ($p) => $p->value(), $found->images());
    expect($paths)->toBe(['services/b.jpg']);
    expect(ServiceImageModel::query()->where('service_id', $service->id()->toString())->count())->toBe(1);
});

it('findByIdOrFail throws when service missing', function (): void {
    $missing = ServiceId::generate();

    expect(fn () => repo()->findByIdOrFail($missing))
        ->toThrow(ServiceNotFoundException::class);
});

it('findByCategory returns services of that category', function (): void {
    $categoryA = makeCategoryRow();
    $categoryB = makeCategoryRow();

    $s1 = Service::createTimeSlot(
        ServiceId::generate(),
        'A1',
        'd',
        Money::fromCents(100, 'RUB'),
        Duration::ofMinutes(30),
        $categoryA,
        null,
    );
    $s2 = Service::createTimeSlot(
        ServiceId::generate(),
        'A2',
        'd',
        Money::fromCents(100, 'RUB'),
        Duration::ofMinutes(30),
        $categoryA,
        null,
    );
    $s3 = Service::createTimeSlot(
        ServiceId::generate(),
        'B1',
        'd',
        Money::fromCents(100, 'RUB'),
        Duration::ofMinutes(30),
        $categoryB,
        null,
    );

    repo()->save($s1);
    repo()->save($s2);
    repo()->save($s3);

    $aServices = repo()->findByCategory($categoryA);
    expect($aServices)->toHaveCount(2);
    $names = array_map(static fn (Service $s) => $s->name(), $aServices);
    sort($names);
    expect($names)->toBe(['A1', 'A2']);
});

it('deletes service', function (): void {
    $categoryId = makeCategoryRow();
    $service = Service::createTimeSlot(
        ServiceId::generate(),
        'Haircut',
        'd',
        Money::fromCents(100, 'RUB'),
        Duration::ofMinutes(30),
        $categoryId,
        null,
    );
    repo()->save($service);

    repo()->delete($service->id());

    expect(repo()->findById($service->id()))->toBeNull();
});
