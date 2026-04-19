<?php

declare(strict_types=1);

use App\Modules\Identity\Domain\Entity\Organization;
use App\Modules\Identity\Domain\Event\OrganizationArchived;
use App\Modules\Identity\Domain\Event\OrganizationCreated;
use App\Modules\Identity\Domain\Event\OrganizationVerified;
use App\Modules\Identity\Domain\Exception\OrganizationArchivedException;
use App\Modules\Identity\Domain\ValueObject\CancellationPolicy;
use App\Modules\Identity\Domain\ValueObject\OrganizationId;
use App\Modules\Identity\Domain\ValueObject\OrganizationSlug;
use App\Modules\Identity\Domain\ValueObject\OrganizationType;

function makeOrganization(): Organization
{
    return Organization::create(
        id: OrganizationId::generate(),
        slug: new OrganizationSlug('salon-savvin'),
        name: ['ru' => 'Салон Саввин', 'en' => 'Salon Savvin'],
        description: ['ru' => 'Лучший салон'],
        type: OrganizationType::SALON,
        city: 'Москва',
        phone: '+7-495-1234567',
        email: 'info@salon.com',
    );
}

function orgContainsEventOfType(array $events, string $class): bool
{
    foreach ($events as $event) {
        if ($event instanceof $class) {
            return true;
        }
    }

    return false;
}

it('creates organization with defaults and records OrganizationCreated event', function (): void {
    $org = makeOrganization();

    expect($org->type)->toBe(OrganizationType::SALON);
    expect($org->isVerified())->toBeFalse();
    expect($org->isArchived())->toBeFalse();
    expect($org->archivedAt())->toBeNull();
    expect($org->cancellationPolicy())->toBe(CancellationPolicy::FLEXIBLE);
    expect($org->rating())->toBe(0.0);
    expect($org->reviewsCount())->toBe(0);
    expect($org->name('ru'))->toBe('Салон Саввин');
    expect($org->name('en'))->toBe('Salon Savvin');
    expect($org->description('ru'))->toBe('Лучший салон');
    expect($org->city())->toBe('Москва');
    expect($org->district())->toBeNull();
    expect($org->logoUrl())->toBeNull();
    expect(orgContainsEventOfType($org->pullDomainEvents(), OrganizationCreated::class))->toBeTrue();
});

it('archives organization and records OrganizationArchived event', function (): void {
    $org = makeOrganization();
    $org->pullDomainEvents();

    $org->archive();

    expect($org->isArchived())->toBeTrue();
    expect($org->archivedAt())->not->toBeNull();
    expect(orgContainsEventOfType($org->pullDomainEvents(), OrganizationArchived::class))->toBeTrue();
});

it('throws when archiving already archived organization', function (): void {
    $org = makeOrganization();
    $org->archive();
    $org->archive();
})->throws(OrganizationArchivedException::class);

it('verifies organization and records OrganizationVerified event', function (): void {
    $org = makeOrganization();
    $org->pullDomainEvents();

    $org->verify();

    expect($org->isVerified())->toBeTrue();
    expect(orgContainsEventOfType($org->pullDomainEvents(), OrganizationVerified::class))->toBeTrue();
});

it('verify is idempotent and emits no event when already verified', function (): void {
    $org = makeOrganization();
    $org->verify();
    $org->pullDomainEvents();

    $org->verify();

    expect($org->isVerified())->toBeTrue();
    expect($org->pullDomainEvents())->toBe([]);
});

it('throws when updating details of archived organization', function (): void {
    $org = makeOrganization();
    $org->archive();

    $org->updateDetails(
        name: ['ru' => 'Новое имя'],
        description: [],
        city: 'Санкт-Петербург',
        district: null,
        phone: '+7-812-7654321',
        email: 'new@salon.com',
    );
})->throws(OrganizationArchivedException::class);

it('updates details and bumps updatedAt', function (): void {
    $org = makeOrganization();
    $before = $org->updatedAt();
    usleep(1000);

    $org->updateDetails(
        name: ['ru' => 'Новое имя', 'en' => 'New Name'],
        description: ['ru' => 'Новое описание'],
        city: 'Санкт-Петербург',
        district: 'Центральный',
        phone: '+7-812-7654321',
        email: 'new@salon.com',
    );

    expect($org->name('ru'))->toBe('Новое имя');
    expect($org->name('en'))->toBe('New Name');
    expect($org->description('ru'))->toBe('Новое описание');
    expect($org->city())->toBe('Санкт-Петербург');
    expect($org->district())->toBe('Центральный');
    expect($org->phone())->toBe('+7-812-7654321');
    expect($org->email())->toBe('new@salon.com');
    expect($org->updatedAt())->not->toEqual($before);
});

it('throws on create when email is invalid', function (): void {
    Organization::create(
        id: OrganizationId::generate(),
        slug: new OrganizationSlug('salon-savvin'),
        name: ['ru' => 'Салон Саввин'],
        description: [],
        type: OrganizationType::SALON,
        city: 'Москва',
        phone: '+7-495-1234567',
        email: 'not-an-email',
    );
})->throws(InvalidArgumentException::class);

it('throws on create when name.ru is empty', function (): void {
    Organization::create(
        id: OrganizationId::generate(),
        slug: new OrganizationSlug('salon-savvin'),
        name: ['en' => 'Only English'],
        description: [],
        type: OrganizationType::SALON,
        city: 'Москва',
        phone: '+7-495-1234567',
        email: 'info@salon.com',
    );
})->throws(InvalidArgumentException::class);

it('throws on create when phone is too short', function (): void {
    Organization::create(
        id: OrganizationId::generate(),
        slug: new OrganizationSlug('salon-savvin'),
        name: ['ru' => 'Салон Саввин'],
        description: [],
        type: OrganizationType::SALON,
        city: 'Москва',
        phone: '12345',
        email: 'info@salon.com',
    );
})->throws(InvalidArgumentException::class);

it('throws on create when description has no ru key but is not empty', function (): void {
    Organization::create(
        id: OrganizationId::generate(),
        slug: new OrganizationSlug('salon-savvin'),
        name: ['ru' => 'Салон Саввин'],
        description: ['en' => 'Only english description'],
        type: OrganizationType::SALON,
        city: 'Москва',
        phone: '+7-495-1234567',
        email: 'info@salon.com',
    );
})->throws(InvalidArgumentException::class);

it('sets logo url', function (): void {
    $org = makeOrganization();

    $org->setLogo('https://cdn.example.com/logo.png');

    expect($org->logoUrl())->toBe('https://cdn.example.com/logo.png');
});

it('clears logo url when null passed', function (): void {
    $org = makeOrganization();
    $org->setLogo('https://cdn.example.com/logo.png');

    $org->setLogo(null);

    expect($org->logoUrl())->toBeNull();
});

it('changes cancellation policy', function (): void {
    $org = makeOrganization();

    $org->changeCancellationPolicy(CancellationPolicy::STRICT);

    expect($org->cancellationPolicy())->toBe(CancellationPolicy::STRICT);
});

it('reconstitute does not record domain events', function (): void {
    $id = OrganizationId::generate();
    $org = Organization::reconstitute(
        id: $id,
        slug: new OrganizationSlug('salon-savvin'),
        name: ['ru' => 'Салон Саввин'],
        description: ['ru' => 'Описание'],
        type: OrganizationType::SALON,
        logoUrl: null,
        city: 'Москва',
        district: null,
        phone: '+7-495-1234567',
        email: 'info@salon.com',
        verified: true,
        cancellationPolicy: CancellationPolicy::MODERATE,
        rating: 4.5,
        reviewsCount: 10,
        archivedAt: null,
        createdAt: new DateTimeImmutable('yesterday'),
        updatedAt: new DateTimeImmutable('now'),
    );

    expect($org->isVerified())->toBeTrue();
    expect($org->cancellationPolicy())->toBe(CancellationPolicy::MODERATE);
    expect($org->rating())->toBe(4.5);
    expect($org->reviewsCount())->toBe(10);
    expect($org->pullDomainEvents())->toBe([]);
});

it('falls back to ru locale when requested locale is missing', function (): void {
    $org = Organization::create(
        id: OrganizationId::generate(),
        slug: new OrganizationSlug('salon-savvin'),
        name: ['ru' => 'Салон Саввин'],
        description: [],
        type: OrganizationType::SALON,
        city: 'Москва',
        phone: '+7-495-1234567',
        email: 'info@salon.com',
    );

    expect($org->name('en'))->toBe('Салон Саввин');
});
