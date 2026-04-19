<?php

declare(strict_types=1);

use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Identity\Domain\ValueObject\OrganizationId;
use App\Modules\Payment\Domain\Entity\PayoutSettings;
use App\Modules\Payment\Domain\Event\PayoutSettingsUpdated;
use App\Modules\Payment\Domain\ValueObject\BankAccount;
use App\Modules\Payment\Domain\ValueObject\PayoutSchedule;
use App\Modules\Payment\Domain\ValueObject\PayoutSettingsId;

function payoutSettingsTestAccount(): BankAccount
{
    return new BankAccount(
        bankName: 'Тинькофф',
        accountNumber: '40702810000000004472',
        accountHolder: 'ООО «Ромашка»',
        bic: '044525974',
    );
}

it('creates PayoutSettings and records PayoutSettingsUpdated event', function (): void {
    $id = PayoutSettingsId::generate();
    $orgId = OrganizationId::generate();

    $settings = PayoutSettings::create(
        $id,
        $orgId,
        payoutSettingsTestAccount(),
        PayoutSchedule::WEEKLY,
        Money::fromCents(100_000),
    );

    expect($settings->id())->toBe($id)
        ->and($settings->organizationId())->toBe($orgId)
        ->and($settings->schedule())->toBe(PayoutSchedule::WEEKLY)
        ->and($settings->minimumPayout()->amount())->toBe(100_000);

    $events = $settings->pullDomainEvents();
    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(PayoutSettingsUpdated::class)
        ->and($events[0]->organizationId()->equals($orgId))->toBeTrue()
        ->and($events[0]->eventName())->toBe('payout.settings_updated')
        ->and($events[0]->payload())->not->toHaveKey('account_number')
        ->and($events[0]->payload())->not->toHaveKey('bic');
});

it('updates bank account and records new event', function (): void {
    $settings = PayoutSettings::create(
        PayoutSettingsId::generate(),
        OrganizationId::generate(),
        payoutSettingsTestAccount(),
        PayoutSchedule::WEEKLY,
        Money::fromCents(100_000),
    );
    $settings->pullDomainEvents();

    $newAccount = new BankAccount('Сбербанк', '40702810000000001111', 'ООО «Ромашка»', '044525225');
    $settings->updateBankAccount($newAccount);

    expect($settings->bankAccount()->equals($newAccount))->toBeTrue();
    $events = $settings->pullDomainEvents();
    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(PayoutSettingsUpdated::class);
});

it('is idempotent when updating with the same bank account', function (): void {
    $account = payoutSettingsTestAccount();
    $settings = PayoutSettings::create(
        PayoutSettingsId::generate(),
        OrganizationId::generate(),
        $account,
        PayoutSchedule::WEEKLY,
        Money::fromCents(100_000),
    );
    $settings->pullDomainEvents();

    $settings->updateBankAccount($account);

    expect($settings->pullDomainEvents())->toBe([]);
});

it('changes schedule and records event', function (): void {
    $settings = PayoutSettings::create(
        PayoutSettingsId::generate(),
        OrganizationId::generate(),
        payoutSettingsTestAccount(),
        PayoutSchedule::WEEKLY,
        Money::fromCents(100_000),
    );
    $settings->pullDomainEvents();

    $settings->changeSchedule(PayoutSchedule::MONTHLY);

    expect($settings->schedule())->toBe(PayoutSchedule::MONTHLY);
    expect($settings->pullDomainEvents())->toHaveCount(1);
});

it('does not record event when setting same schedule', function (): void {
    $settings = PayoutSettings::create(
        PayoutSettingsId::generate(),
        OrganizationId::generate(),
        payoutSettingsTestAccount(),
        PayoutSchedule::WEEKLY,
        Money::fromCents(100_000),
    );
    $settings->pullDomainEvents();

    $settings->changeSchedule(PayoutSchedule::WEEKLY);

    expect($settings->pullDomainEvents())->toBe([]);
});

it('changes minimum payout amount', function (): void {
    $settings = PayoutSettings::create(
        PayoutSettingsId::generate(),
        OrganizationId::generate(),
        payoutSettingsTestAccount(),
        PayoutSchedule::WEEKLY,
        Money::fromCents(100_000),
    );
    $settings->pullDomainEvents();

    $settings->changeMinimumPayout(Money::fromCents(500_000));

    expect($settings->minimumPayout()->amount())->toBe(500_000);
    expect($settings->pullDomainEvents())->toHaveCount(1);
});

it('reconstitutes PayoutSettings from persistence without recording events', function (): void {
    $settings = PayoutSettings::reconstitute(
        PayoutSettingsId::generate(),
        OrganizationId::generate(),
        payoutSettingsTestAccount(),
        PayoutSchedule::BIWEEKLY,
        Money::fromCents(250_000),
    );

    expect($settings->schedule())->toBe(PayoutSchedule::BIWEEKLY)
        ->and($settings->minimumPayout()->amount())->toBe(250_000)
        ->and($settings->pullDomainEvents())->toBe([]);
});
