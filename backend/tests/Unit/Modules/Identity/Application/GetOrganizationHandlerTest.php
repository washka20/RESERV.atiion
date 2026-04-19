<?php

declare(strict_types=1);

use App\Modules\Identity\Application\DTO\OrganizationDTO;
use App\Modules\Identity\Application\Query\GetOrganization\GetOrganizationHandler;
use App\Modules\Identity\Application\Query\GetOrganization\GetOrganizationQuery;
use App\Modules\Identity\Application\Query\GetOrganizationBySlug\GetOrganizationBySlugHandler;
use App\Modules\Identity\Application\Query\GetOrganizationBySlug\GetOrganizationBySlugQuery;
use App\Modules\Identity\Domain\Entity\Organization;
use App\Modules\Identity\Domain\Exception\OrganizationNotFoundException;
use App\Modules\Identity\Domain\Repository\OrganizationRepositoryInterface;
use App\Modules\Identity\Domain\ValueObject\OrganizationId;
use App\Modules\Identity\Domain\ValueObject\OrganizationSlug;
use App\Modules\Identity\Domain\ValueObject\OrganizationType;

function makeOrgForQuery(string $slug = 'salon-q'): Organization
{
    $org = Organization::create(
        id: OrganizationId::generate(),
        slug: new OrganizationSlug($slug),
        name: ['ru' => 'Запрос'],
        description: [],
        type: OrganizationType::SALON,
        city: 'Москва',
        phone: '+7999111',
        email: 'q@example.com',
    );
    $org->pullDomainEvents();

    return $org;
}

it('returns organization DTO by id', function (): void {
    $org = makeOrgForQuery();
    $repo = Mockery::mock(OrganizationRepositoryInterface::class);
    $repo->shouldReceive('findById')->once()->andReturn($org);

    $handler = new GetOrganizationHandler($repo);
    $dto = $handler->handle(new GetOrganizationQuery($org->id->toString()));

    expect($dto)->toBeInstanceOf(OrganizationDTO::class);
    expect($dto->slug)->toBe('salon-q');
    expect($dto->type)->toBe('salon');
});

it('throws OrganizationNotFoundException when id missing', function (): void {
    $repo = Mockery::mock(OrganizationRepositoryInterface::class);
    $repo->shouldReceive('findById')->once()->andReturnNull();

    $handler = new GetOrganizationHandler($repo);
    $handler->handle(new GetOrganizationQuery(OrganizationId::generate()->toString()));
})->throws(OrganizationNotFoundException::class);

it('returns organization DTO by slug', function (): void {
    $org = makeOrgForQuery('salon-sl');
    $repo = Mockery::mock(OrganizationRepositoryInterface::class);
    $repo->shouldReceive('findBySlug')->once()->andReturn($org);

    $handler = new GetOrganizationBySlugHandler($repo);
    $dto = $handler->handle(new GetOrganizationBySlugQuery('salon-sl'));

    expect($dto->slug)->toBe('salon-sl');
});

it('throws OrganizationNotFoundException when slug missing', function (): void {
    $repo = Mockery::mock(OrganizationRepositoryInterface::class);
    $repo->shouldReceive('findBySlug')->once()->andReturnNull();

    $handler = new GetOrganizationBySlugHandler($repo);
    $handler->handle(new GetOrganizationBySlugQuery('not-found'));
})->throws(OrganizationNotFoundException::class);
