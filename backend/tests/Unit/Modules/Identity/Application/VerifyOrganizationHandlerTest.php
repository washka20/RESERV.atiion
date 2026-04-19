<?php

declare(strict_types=1);

use App\Modules\Identity\Application\Command\VerifyOrganization\VerifyOrganizationCommand;
use App\Modules\Identity\Application\Command\VerifyOrganization\VerifyOrganizationHandler;
use App\Modules\Identity\Domain\Entity\Organization;
use App\Modules\Identity\Domain\Event\OrganizationVerified;
use App\Modules\Identity\Domain\Exception\OrganizationNotFoundException;
use App\Modules\Identity\Domain\Repository\OrganizationRepositoryInterface;
use App\Modules\Identity\Domain\ValueObject\OrganizationId;
use App\Modules\Identity\Domain\ValueObject\OrganizationSlug;
use App\Modules\Identity\Domain\ValueObject\OrganizationType;
use Tests\Unit\Modules\Identity\Application\Support\PassthroughTransactionManager;
use Tests\Unit\Modules\Identity\Application\Support\RecordingEventDispatcher;

function makeOrgForVerify(bool $alreadyVerified = false): Organization
{
    $org = Organization::create(
        id: OrganizationId::generate(),
        slug: new OrganizationSlug('salon-verify'),
        name: ['ru' => 'Тест'],
        description: [],
        type: OrganizationType::SALON,
        city: 'Москва',
        phone: '+7999111',
        email: 'ver@example.com',
    );
    $org->pullDomainEvents();

    if ($alreadyVerified) {
        $org->verify();
        $org->pullDomainEvents();
    }

    return $org;
}

it('verifies organization and dispatches event (admin-only)', function (): void {
    $org = makeOrgForVerify();

    $orgs = Mockery::mock(OrganizationRepositoryInterface::class);
    $orgs->shouldReceive('findById')->once()->andReturn($org);
    $orgs->shouldReceive('save')->once()->with(Mockery::type(Organization::class));

    $dispatcher = new RecordingEventDispatcher;
    $handler = new VerifyOrganizationHandler($orgs, $dispatcher, new PassthroughTransactionManager);

    $handler->handle(new VerifyOrganizationCommand($org->id->toString()));

    expect($org->isVerified())->toBeTrue();
    expect($dispatcher->events)->toHaveCount(1);
    expect($dispatcher->events[0])->toBeInstanceOf(OrganizationVerified::class);
});

it('is idempotent when organization already verified', function (): void {
    $org = makeOrgForVerify(alreadyVerified: true);

    $orgs = Mockery::mock(OrganizationRepositoryInterface::class);
    $orgs->shouldReceive('findById')->once()->andReturn($org);
    $orgs->shouldReceive('save')->once();

    $dispatcher = new RecordingEventDispatcher;
    $handler = new VerifyOrganizationHandler($orgs, $dispatcher, new PassthroughTransactionManager);

    $handler->handle(new VerifyOrganizationCommand($org->id->toString()));

    expect($org->isVerified())->toBeTrue();
    expect($dispatcher->events)->toBe([]);
});

it('throws OrganizationNotFoundException when id not found', function (): void {
    $orgs = Mockery::mock(OrganizationRepositoryInterface::class);
    $orgs->shouldReceive('findById')->once()->andReturnNull();

    $handler = new VerifyOrganizationHandler($orgs, new RecordingEventDispatcher, new PassthroughTransactionManager);

    $handler->handle(new VerifyOrganizationCommand(OrganizationId::generate()->toString()));
})->throws(OrganizationNotFoundException::class);
