<?php

declare(strict_types=1);

use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Identity\Domain\ValueObject\OrganizationId;
use App\Modules\Payment\Domain\Entity\PayoutSettings;
use App\Modules\Payment\Domain\Repository\PayoutSettingsRepositoryInterface;
use App\Modules\Payment\Domain\ValueObject\BankAccount;
use App\Modules\Payment\Domain\ValueObject\PayoutSchedule;
use App\Modules\Payment\Domain\ValueObject\PayoutSettingsId;
use App\Modules\Payment\Infrastructure\Persistence\Model\PayoutSettingsModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;

uses(RefreshDatabase::class);

function payoutSettingsRepo(): PayoutSettingsRepositoryInterface
{
    return app(PayoutSettingsRepositoryInterface::class);
}

function freshBankAccount(): BankAccount
{
    return new BankAccount(
        bankName: 'Тинькофф',
        accountNumber: '40702810000000004472',
        accountHolder: 'ООО «Ромашка»',
        bic: '044525974',
    );
}

it('saves new settings and finds by organization_id with decrypted account number', function (): void {
    $orgId = insertOrganizationForTests('payout-org');
    $settings = PayoutSettings::create(
        PayoutSettingsId::generate(),
        $orgId,
        freshBankAccount(),
        PayoutSchedule::WEEKLY,
        Money::fromCents(100_000),
    );

    payoutSettingsRepo()->save($settings);

    $found = payoutSettingsRepo()->findByOrganizationId($orgId);
    expect($found)->not->toBeNull()
        ->and($found->organizationId()->equals($orgId))->toBeTrue()
        ->and($found->bankAccount()->accountNumber)->toBe('40702810000000004472')
        ->and($found->bankAccount()->bic)->toBe('044525974')
        ->and($found->schedule())->toBe(PayoutSchedule::WEEKLY)
        ->and($found->minimumPayout()->amount())->toBe(100_000);
});

it('stores account number encrypted and saves last-4 masked snapshot', function (): void {
    $orgId = insertOrganizationForTests('payout-org');
    $settings = PayoutSettings::create(
        PayoutSettingsId::generate(),
        $orgId,
        freshBankAccount(),
        PayoutSchedule::WEEKLY,
        Money::fromCents(100_000),
    );

    payoutSettingsRepo()->save($settings);

    $row = PayoutSettingsModel::query()->where('organization_id', $orgId->toString())->first();
    expect($row->account_number_encrypted)->not->toBe('40702810000000004472')
        ->and(Crypt::decryptString((string) $row->account_number_encrypted))->toBe('40702810000000004472')
        ->and((string) $row->account_number_masked)->toBe('•••• 4472');
});

it('upserts existing settings on save without creating a second row', function (): void {
    $orgId = insertOrganizationForTests('payout-org');
    $settings = PayoutSettings::create(
        PayoutSettingsId::generate(),
        $orgId,
        freshBankAccount(),
        PayoutSchedule::WEEKLY,
        Money::fromCents(100_000),
    );
    payoutSettingsRepo()->save($settings);

    $settings->changeSchedule(PayoutSchedule::MONTHLY);
    $settings->changeMinimumPayout(Money::fromCents(500_000));
    payoutSettingsRepo()->save($settings);

    $count = PayoutSettingsModel::query()->where('organization_id', $orgId->toString())->count();
    expect($count)->toBe(1);

    $reloaded = payoutSettingsRepo()->findByOrganizationId($orgId);
    expect($reloaded->schedule())->toBe(PayoutSchedule::MONTHLY)
        ->and($reloaded->minimumPayout()->amount())->toBe(500_000);
});

it('returns null when no settings for organization', function (): void {
    $missing = OrganizationId::generate();
    expect(payoutSettingsRepo()->findByOrganizationId($missing))->toBeNull();
});
