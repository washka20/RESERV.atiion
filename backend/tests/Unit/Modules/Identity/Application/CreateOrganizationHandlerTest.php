<?php

declare(strict_types=1);

use App\Modules\Identity\Application\Command\CreateOrganization\CreateOrganizationCommand;
use App\Modules\Identity\Application\Command\CreateOrganization\CreateOrganizationHandler;
use App\Modules\Identity\Application\DTO\OrganizationDTO;
use App\Modules\Identity\Domain\Entity\Membership;
use App\Modules\Identity\Domain\Entity\Organization;
use App\Modules\Identity\Domain\Entity\User;
use App\Modules\Identity\Domain\Event\MembershipGranted;
use App\Modules\Identity\Domain\Event\OrganizationCreated;
use App\Modules\Identity\Domain\Exception\UserNotFoundException;
use App\Modules\Identity\Domain\Repository\MembershipRepositoryInterface;
use App\Modules\Identity\Domain\Repository\OrganizationRepositoryInterface;
use App\Modules\Identity\Domain\Repository\UserRepositoryInterface;
use App\Modules\Identity\Domain\Service\SlugGeneratorInterface;
use App\Modules\Identity\Domain\ValueObject\Email;
use App\Modules\Identity\Domain\ValueObject\FullName;
use App\Modules\Identity\Domain\ValueObject\HashedPassword;
use App\Modules\Identity\Domain\ValueObject\MembershipRole;
use App\Modules\Identity\Domain\ValueObject\OrganizationSlug;
use App\Modules\Identity\Domain\ValueObject\UserId;
use Tests\Unit\Modules\Identity\Application\Support\InMemoryPasswordHasher;
use Tests\Unit\Modules\Identity\Application\Support\PassthroughTransactionManager;
use Tests\Unit\Modules\Identity\Application\Support\RecordingEventDispatcher;

function makeExistingUserForOrg(): User
{
    $hasher = new InMemoryPasswordHasher;

    return User::register(
        UserId::generate(),
        new Email('creator@example.com'),
        HashedPassword::fromPlaintext('password123', $hasher),
        new FullName('Alice', 'Creator', null),
    );
}

it('creates organization with owner membership and dispatches two events', function (): void {
    $user = makeExistingUserForOrg();

    $users = Mockery::mock(UserRepositoryInterface::class);
    $users->shouldReceive('findById')->once()->andReturn($user);

    $orgs = Mockery::mock(OrganizationRepositoryInterface::class);
    $orgs->shouldReceive('save')->once()->with(Mockery::type(Organization::class));

    $memberships = Mockery::mock(MembershipRepositoryInterface::class);
    $memberships->shouldReceive('save')->once()->with(Mockery::type(Membership::class));

    $slugs = Mockery::mock(SlugGeneratorInterface::class);
    $slugs->shouldReceive('generate')->once()->with('Салон Красоты')->andReturn(new OrganizationSlug('salon-krasoty'));

    $dispatcher = new RecordingEventDispatcher;
    $handler = new CreateOrganizationHandler(
        $orgs,
        $memberships,
        $users,
        $slugs,
        $dispatcher,
        new PassthroughTransactionManager,
    );

    $dto = $handler->handle(new CreateOrganizationCommand(
        userId: $user->id()->toString(),
        name: ['ru' => 'Салон Красоты'],
        description: [],
        type: 'salon',
        city: 'Москва',
        phone: '+7999111',
        email: 'salon@example.com',
    ));

    expect($dto)->toBeInstanceOf(OrganizationDTO::class);
    expect($dto->slug)->toBe('salon-krasoty');
    expect($dto->type)->toBe('salon');
    expect($dto->name)->toBe(['ru' => 'Салон Красоты']);
    expect($dto->verified)->toBeFalse();
    expect($dto->archived)->toBeFalse();

    expect($dispatcher->events)->toHaveCount(2);
    expect($dispatcher->events[0])->toBeInstanceOf(OrganizationCreated::class);
    expect($dispatcher->events[1])->toBeInstanceOf(MembershipGranted::class);
    expect($dispatcher->events[1]->role())->toBe(MembershipRole::OWNER);
});

it('throws UserNotFoundException if creator user does not exist', function (): void {
    $users = Mockery::mock(UserRepositoryInterface::class);
    $users->shouldReceive('findById')->once()->andReturnNull();

    $orgs = Mockery::mock(OrganizationRepositoryInterface::class);
    $memberships = Mockery::mock(MembershipRepositoryInterface::class);
    $slugs = Mockery::mock(SlugGeneratorInterface::class);

    $handler = new CreateOrganizationHandler(
        $orgs,
        $memberships,
        $users,
        $slugs,
        new RecordingEventDispatcher,
        new PassthroughTransactionManager,
    );

    $handler->handle(new CreateOrganizationCommand(
        userId: UserId::generate()->toString(),
        name: ['ru' => 'Тест'],
        description: [],
        type: 'salon',
        city: 'Москва',
        phone: '+7999111',
        email: 'test@example.com',
    ));
})->throws(UserNotFoundException::class);

it('fails if invalid email passed to command (propagates Organization invariant)', function (): void {
    $user = makeExistingUserForOrg();

    $users = Mockery::mock(UserRepositoryInterface::class);
    $users->shouldReceive('findById')->once()->andReturn($user);

    $orgs = Mockery::mock(OrganizationRepositoryInterface::class);
    $memberships = Mockery::mock(MembershipRepositoryInterface::class);
    $slugs = Mockery::mock(SlugGeneratorInterface::class);
    $slugs->shouldReceive('generate')->once()->andReturn(new OrganizationSlug('ok-slug'));

    $handler = new CreateOrganizationHandler(
        $orgs,
        $memberships,
        $users,
        $slugs,
        new RecordingEventDispatcher,
        new PassthroughTransactionManager,
    );

    $handler->handle(new CreateOrganizationCommand(
        userId: $user->id()->toString(),
        name: ['ru' => 'Тест'],
        description: [],
        type: 'salon',
        city: 'Москва',
        phone: '+7999111',
        email: 'not-an-email',
    ));
})->throws(InvalidArgumentException::class);

it('fails if unknown organization type in command', function (): void {
    $user = makeExistingUserForOrg();

    $users = Mockery::mock(UserRepositoryInterface::class);
    $users->shouldReceive('findById')->once()->andReturn($user);

    $orgs = Mockery::mock(OrganizationRepositoryInterface::class);
    $memberships = Mockery::mock(MembershipRepositoryInterface::class);
    $slugs = Mockery::mock(SlugGeneratorInterface::class);
    $slugs->shouldReceive('generate')->once()->andReturn(new OrganizationSlug('ok-slug'));

    $handler = new CreateOrganizationHandler(
        $orgs,
        $memberships,
        $users,
        $slugs,
        new RecordingEventDispatcher,
        new PassthroughTransactionManager,
    );

    $handler->handle(new CreateOrganizationCommand(
        userId: $user->id()->toString(),
        name: ['ru' => 'Тест'],
        description: [],
        type: 'unknown-type',
        city: 'Москва',
        phone: '+7999111',
        email: 'test@example.com',
    ));
})->throws(ValueError::class);
