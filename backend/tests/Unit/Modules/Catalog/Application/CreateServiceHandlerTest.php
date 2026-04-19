<?php

declare(strict_types=1);

use App\Modules\Catalog\Application\Command\CreateService\CreateServiceCommand;
use App\Modules\Catalog\Application\Command\CreateService\CreateServiceHandler;
use App\Modules\Catalog\Domain\Entity\Category;
use App\Modules\Catalog\Domain\Entity\Service;
use App\Modules\Catalog\Domain\Event\ServiceCreated;
use App\Modules\Catalog\Domain\Exception\CategoryNotFoundException;
use App\Modules\Catalog\Domain\Repository\CategoryRepositoryInterface;
use App\Modules\Catalog\Domain\Repository\ServiceRepositoryInterface;
use App\Modules\Catalog\Domain\ValueObject\CategoryId;
use App\Modules\Catalog\Domain\ValueObject\ServiceType;
use App\Modules\Identity\Domain\Entity\Organization;
use App\Modules\Identity\Domain\Exception\OrganizationArchivedException;
use App\Modules\Identity\Domain\Exception\OrganizationNotFoundException;
use App\Modules\Identity\Domain\Repository\OrganizationRepositoryInterface;
use App\Modules\Identity\Domain\ValueObject\CancellationPolicy;
use App\Modules\Identity\Domain\ValueObject\OrganizationId;
use App\Modules\Identity\Domain\ValueObject\OrganizationSlug;
use App\Modules\Identity\Domain\ValueObject\OrganizationType;
use Tests\Unit\Modules\Catalog\Application\Support\RecordingEventDispatcher;

function makeCategory(?CategoryId $id = null): Category
{
    return Category::restore(
        $id ?? CategoryId::generate(),
        'Beauty',
        'beauty',
        0,
        [],
    );
}

function makeOrganizationForCatalog(?OrganizationId $id = null, bool $archived = false): Organization
{
    $org = Organization::reconstitute(
        id: $id ?? OrganizationId::generate(),
        slug: new OrganizationSlug('acme-studio'),
        name: ['ru' => 'Acme'],
        description: [],
        type: OrganizationType::SALON,
        logoUrl: null,
        city: 'Moscow',
        district: null,
        phone: '+7 000 000 00 00',
        email: 'org@example.com',
        verified: false,
        cancellationPolicy: CancellationPolicy::FLEXIBLE,
        rating: 0.0,
        reviewsCount: 0,
        archivedAt: $archived ? new DateTimeImmutable : null,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );

    return $org;
}

it('creates TIME_SLOT service and dispatches ServiceCreated', function (): void {
    $services = Mockery::mock(ServiceRepositoryInterface::class);
    $categories = Mockery::mock(CategoryRepositoryInterface::class);
    $organizations = Mockery::mock(OrganizationRepositoryInterface::class);
    $dispatcher = new RecordingEventDispatcher;

    $categoryId = CategoryId::generate();
    $organizationId = OrganizationId::generate();
    $categories->shouldReceive('findByIdOrFail')->once()->andReturn(makeCategory($categoryId));
    $organizations->shouldReceive('findById')->once()->andReturn(makeOrganizationForCatalog($organizationId));
    $services->shouldReceive('save')->once()->with(Mockery::type(Service::class));

    $handler = new CreateServiceHandler($services, $categories, $organizations, $dispatcher);
    $id = $handler->handle(new CreateServiceCommand(
        name: 'Haircut',
        description: 'Classic cut',
        priceAmount: 150000,
        priceCurrency: 'RUB',
        type: 'time_slot',
        categoryId: $categoryId->toString(),
        organizationId: $organizationId->toString(),
        durationMinutes: 60,
    ));

    expect($id)->toMatch('/^[0-9a-f-]{36}$/');
    expect($dispatcher->events)->toHaveCount(1);
    expect($dispatcher->events[0])->toBeInstanceOf(ServiceCreated::class);
    expect($dispatcher->events[0]->type())->toBe(ServiceType::TIME_SLOT);
});

it('creates QUANTITY service', function (): void {
    $services = Mockery::mock(ServiceRepositoryInterface::class);
    $categories = Mockery::mock(CategoryRepositoryInterface::class);
    $organizations = Mockery::mock(OrganizationRepositoryInterface::class);
    $dispatcher = new RecordingEventDispatcher;

    $categoryId = CategoryId::generate();
    $organizationId = OrganizationId::generate();
    $categories->shouldReceive('findByIdOrFail')->once()->andReturn(makeCategory($categoryId));
    $organizations->shouldReceive('findById')->once()->andReturn(makeOrganizationForCatalog($organizationId));
    $services->shouldReceive('save')->once();

    $handler = new CreateServiceHandler($services, $categories, $organizations, $dispatcher);
    $handler->handle(new CreateServiceCommand(
        name: 'Tent',
        description: 'Rent',
        priceAmount: 500000,
        priceCurrency: 'RUB',
        type: 'quantity',
        categoryId: $categoryId->toString(),
        organizationId: $organizationId->toString(),
        totalQuantity: 10,
    ));

    expect($dispatcher->events[0])->toBeInstanceOf(ServiceCreated::class);
    expect($dispatcher->events[0]->type())->toBe(ServiceType::QUANTITY);
});

it('throws if category missing', function (): void {
    $services = Mockery::mock(ServiceRepositoryInterface::class);
    $categories = Mockery::mock(CategoryRepositoryInterface::class);
    $organizations = Mockery::mock(OrganizationRepositoryInterface::class);
    $categoryId = CategoryId::generate();
    $categories->shouldReceive('findByIdOrFail')->once()
        ->andThrow(CategoryNotFoundException::byId($categoryId));

    $handler = new CreateServiceHandler($services, $categories, $organizations, new RecordingEventDispatcher);
    $handler->handle(new CreateServiceCommand(
        'n', 'd', 100, 'RUB', 'time_slot', $categoryId->toString(), OrganizationId::generate()->toString(), durationMinutes: 30,
    ));
})->throws(CategoryNotFoundException::class);

it('throws if organization missing', function (): void {
    $services = Mockery::mock(ServiceRepositoryInterface::class);
    $categories = Mockery::mock(CategoryRepositoryInterface::class);
    $organizations = Mockery::mock(OrganizationRepositoryInterface::class);
    $categoryId = CategoryId::generate();
    $organizationId = OrganizationId::generate();
    $categories->shouldReceive('findByIdOrFail')->once()->andReturn(makeCategory($categoryId));
    $organizations->shouldReceive('findById')->once()->andReturn(null);

    $handler = new CreateServiceHandler($services, $categories, $organizations, new RecordingEventDispatcher);
    $handler->handle(new CreateServiceCommand(
        'n', 'd', 100, 'RUB', 'time_slot', $categoryId->toString(), $organizationId->toString(), durationMinutes: 30,
    ));
})->throws(OrganizationNotFoundException::class);

it('throws if organization archived', function (): void {
    $services = Mockery::mock(ServiceRepositoryInterface::class);
    $categories = Mockery::mock(CategoryRepositoryInterface::class);
    $organizations = Mockery::mock(OrganizationRepositoryInterface::class);
    $categoryId = CategoryId::generate();
    $organizationId = OrganizationId::generate();
    $categories->shouldReceive('findByIdOrFail')->once()->andReturn(makeCategory($categoryId));
    $organizations->shouldReceive('findById')->once()->andReturn(makeOrganizationForCatalog($organizationId, archived: true));

    $handler = new CreateServiceHandler($services, $categories, $organizations, new RecordingEventDispatcher);
    $handler->handle(new CreateServiceCommand(
        'n', 'd', 100, 'RUB', 'time_slot', $categoryId->toString(), $organizationId->toString(), durationMinutes: 30,
    ));
})->throws(OrganizationArchivedException::class);

it('throws if TIME_SLOT without duration', function (): void {
    $services = Mockery::mock(ServiceRepositoryInterface::class);
    $categories = Mockery::mock(CategoryRepositoryInterface::class);
    $organizations = Mockery::mock(OrganizationRepositoryInterface::class);
    $categoryId = CategoryId::generate();
    $organizationId = OrganizationId::generate();
    $categories->shouldReceive('findByIdOrFail')->once()->andReturn(makeCategory($categoryId));
    $organizations->shouldReceive('findById')->once()->andReturn(makeOrganizationForCatalog($organizationId));

    $handler = new CreateServiceHandler($services, $categories, $organizations, new RecordingEventDispatcher);
    $handler->handle(new CreateServiceCommand(
        'n', 'd', 100, 'RUB', 'time_slot', $categoryId->toString(), $organizationId->toString(),
    ));
})->throws(InvalidArgumentException::class);

it('throws if QUANTITY without totalQuantity', function (): void {
    $services = Mockery::mock(ServiceRepositoryInterface::class);
    $categories = Mockery::mock(CategoryRepositoryInterface::class);
    $organizations = Mockery::mock(OrganizationRepositoryInterface::class);
    $categoryId = CategoryId::generate();
    $organizationId = OrganizationId::generate();
    $categories->shouldReceive('findByIdOrFail')->once()->andReturn(makeCategory($categoryId));
    $organizations->shouldReceive('findById')->once()->andReturn(makeOrganizationForCatalog($organizationId));

    $handler = new CreateServiceHandler($services, $categories, $organizations, new RecordingEventDispatcher);
    $handler->handle(new CreateServiceCommand(
        'n', 'd', 100, 'RUB', 'quantity', $categoryId->toString(), $organizationId->toString(),
    ));
})->throws(InvalidArgumentException::class);
