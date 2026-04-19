<?php

declare(strict_types=1);

use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Identity\Domain\ValueObject\OrganizationId;
use App\Modules\Payment\Application\Command\UpdatePayoutSettings\UpdatePayoutSettingsCommand;
use App\Modules\Payment\Application\Command\UpdatePayoutSettings\UpdatePayoutSettingsHandler;
use App\Modules\Payment\Domain\Entity\PayoutSettings;
use App\Modules\Payment\Domain\Event\PayoutSettingsUpdated;
use App\Modules\Payment\Domain\Repository\PayoutSettingsRepositoryInterface;
use App\Modules\Payment\Domain\ValueObject\BankAccount;
use App\Modules\Payment\Domain\ValueObject\PayoutSchedule;
use App\Modules\Payment\Domain\ValueObject\PayoutSettingsId;
use App\Shared\Application\Identity\MembershipLookupInterface;
use App\Shared\Application\Outbox\OutboxPublisherInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Tests\Unit\Modules\Payment\Application\PassthroughTransactionManager;

function updatePayoutSettingsCmd(string $userId, string $orgId): UpdatePayoutSettingsCommand
{
    return new UpdatePayoutSettingsCommand(
        userId: $userId,
        organizationId: $orgId,
        bankName: 'Тинькофф',
        accountNumber: '40702810000000004472',
        accountHolder: 'ООО «Ромашка»',
        bic: '044525974',
        schedule: 'weekly',
        minimumPayoutCents: 100_000,
    );
}

it('throws AuthorizationException when user is not organization owner', function (): void {
    $userId = '11111111-1111-1111-1111-111111111111';
    $orgId = '22222222-2222-2222-2222-222222222222';

    $memberships = mock(MembershipLookupInterface::class);
    $memberships->shouldReceive('isOwner')->with($userId, $orgId)->once()->andReturn(false);

    $repo = mock(PayoutSettingsRepositoryInterface::class);
    $publisher = mock(OutboxPublisherInterface::class);

    $handler = new UpdatePayoutSettingsHandler($repo, $memberships, $publisher, new PassthroughTransactionManager);
    $handler->handle(updatePayoutSettingsCmd($userId, $orgId));
})->throws(AuthorizationException::class);

it('creates new PayoutSettings when none exist and publishes PayoutSettingsUpdated', function (): void {
    $userId = '11111111-1111-1111-1111-111111111111';
    $orgId = '22222222-2222-2222-2222-222222222222';

    $memberships = mock(MembershipLookupInterface::class);
    $memberships->shouldReceive('isOwner')->with($userId, $orgId)->once()->andReturn(true);

    $savedSettings = null;
    $repo = mock(PayoutSettingsRepositoryInterface::class);
    $repo->shouldReceive('findByOrganizationId')->once()->andReturn(null);
    $repo->shouldReceive('save')->once()->andReturnUsing(function (PayoutSettings $s) use (&$savedSettings): void {
        $savedSettings = $s;
    });

    $publishedEvent = null;
    $publishedReliable = null;
    $publisher = mock(OutboxPublisherInterface::class);
    $publisher->shouldReceive('publish')->once()->andReturnUsing(
        function ($event, bool $reliable) use (&$publishedEvent, &$publishedReliable): void {
            $publishedEvent = $event;
            $publishedReliable = $reliable;
        }
    );

    $handler = new UpdatePayoutSettingsHandler($repo, $memberships, $publisher, new PassthroughTransactionManager);
    $handler->handle(updatePayoutSettingsCmd($userId, $orgId));

    expect($savedSettings)->toBeInstanceOf(PayoutSettings::class)
        ->and($savedSettings->organizationId()->toString())->toBe($orgId)
        ->and($savedSettings->schedule())->toBe(PayoutSchedule::WEEKLY)
        ->and($savedSettings->minimumPayout()->amount())->toBe(100_000)
        ->and($publishedEvent)->toBeInstanceOf(PayoutSettingsUpdated::class)
        ->and($publishedReliable)->toBeFalse();
});

it('updates existing PayoutSettings when one already exists', function (): void {
    $userId = '11111111-1111-1111-1111-111111111111';
    $orgId = '22222222-2222-2222-2222-222222222222';

    $memberships = mock(MembershipLookupInterface::class);
    $memberships->shouldReceive('isOwner')->with($userId, $orgId)->once()->andReturn(true);

    $existing = PayoutSettings::create(
        PayoutSettingsId::generate(),
        new OrganizationId($orgId),
        new BankAccount(
            bankName: 'Сбербанк',
            accountNumber: '40702810000000001111',
            accountHolder: 'ООО «Ромашка»',
            bic: '044525225',
        ),
        PayoutSchedule::MONTHLY,
        Money::fromCents(500_000),
    );
    $existing->pullDomainEvents();

    $repo = mock(PayoutSettingsRepositoryInterface::class);
    $repo->shouldReceive('findByOrganizationId')->once()->andReturn($existing);
    $repo->shouldReceive('save')->once();

    $publisher = mock(OutboxPublisherInterface::class);
    $publisher->shouldReceive('publish')->atLeast()->once();

    $handler = new UpdatePayoutSettingsHandler($repo, $memberships, $publisher, new PassthroughTransactionManager);
    $handler->handle(updatePayoutSettingsCmd($userId, $orgId));

    expect($existing->bankAccount()->bankName)->toBe('Тинькофф')
        ->and($existing->schedule())->toBe(PayoutSchedule::WEEKLY)
        ->and($existing->minimumPayout()->amount())->toBe(100_000);
});
