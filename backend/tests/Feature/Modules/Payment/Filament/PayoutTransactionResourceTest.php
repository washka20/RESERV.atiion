<?php

declare(strict_types=1);

use App\Modules\Identity\Infrastructure\Persistence\Model\UserModel;
use App\Modules\Payment\Infrastructure\Persistence\Model\PayoutTransactionModel;
use App\Modules\Payment\Interface\Filament\Resource\PayoutTransactionResource;
use App\Modules\Payment\Interface\Filament\Resource\PayoutTransactionResource\Pages\ListPayoutTransactions;
use Database\Seeders\SpatieRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(SpatieRoleSeeder::class);
});

/*
 * Создаёт admin, логинит в web-guard.
 */
function payoutAdminUser(): UserModel
{
    $admin = UserModel::factory()->create();
    $admin->assignRole('admin');
    actingAs($admin, 'web');

    return $admin;
}

/*
 * Создаёт бронирование + payment + payout в БД напрямую.
 *
 * @return string payoutId
 */
function payoutCreateFixture(string $status = 'pending'): string
{
    $user = bookingInsertUser('payout-fx-'.Str::random(6).'@test.com');
    $orgId = insertOrganizationForTests('payout-fx');
    $categoryId = insertCategory('PayoutFx'.Str::random(4));
    $service = saveTimeSlotService('Fx svc '.Str::random(4), $categoryId, organizationId: $orgId);
    $slotId = bookingInsertTimeSlot($service->id());
    $bookingId = bookingInsertTimeSlotBooking(
        new App\Modules\Identity\Domain\ValueObject\UserId((string) $user->getAuthIdentifier()),
        $service->id(),
        $slotId,
    );

    $paymentId = (string) Str::uuid();
    DB::table('payments')->insert([
        'id' => $paymentId,
        'booking_id' => $bookingId,
        'amount_cents' => 240_000,
        'currency' => 'RUB',
        'status' => 'paid',
        'method' => 'card',
        'provider_ref' => 'test-ref',
        'marketplace_fee_percent' => 10,
        'platform_fee_cents' => 24_000,
        'net_amount_cents' => 216_000,
        'paid_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $payoutId = (string) Str::uuid();
    DB::table('payout_transactions')->insert([
        'id' => $payoutId,
        'booking_id' => $bookingId,
        'organization_id' => $orgId->toString(),
        'payment_id' => $paymentId,
        'gross_amount_cents' => 240_000,
        'platform_fee_cents' => 24_000,
        'net_amount_cents' => 216_000,
        'currency' => 'RUB',
        'status' => $status,
        'scheduled_at' => $status === 'pending' ? now()->addDays(7) : null,
        'paid_at' => $status === 'paid' ? now() : null,
        'failure_reason' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return $payoutId;
}

it('admin sees payouts list page', function (): void {
    payoutAdminUser();

    $this->get(PayoutTransactionResource::getUrl('index'))->assertOk();
});

it('customer cannot access payouts admin page', function (): void {
    $customer = UserModel::factory()->create();
    $customer->assignRole('customer');
    actingAs($customer, 'web');

    $this->get(PayoutTransactionResource::getUrl('index'))->assertForbidden();
});

it('admin marks pending payout as paid via row action', function (): void {
    payoutAdminUser();
    $payoutId = payoutCreateFixture('pending');

    $record = PayoutTransactionModel::query()->findOrFail($payoutId);

    Livewire::test(ListPayoutTransactions::class)
        ->callTableAction('markPaid', $record)
        ->assertHasNoTableActionErrors();

    $fresh = PayoutTransactionModel::query()->findOrFail($payoutId);
    expect($fresh->status)->toBe('paid')
        ->and($fresh->paid_at)->not->toBeNull();
});

it('markPaid action hidden for already paid payout', function (): void {
    payoutAdminUser();
    $payoutId = payoutCreateFixture('paid');

    $record = PayoutTransactionModel::query()->findOrFail($payoutId);

    Livewire::test(ListPayoutTransactions::class)
        ->assertTableActionHidden('markPaid', $record);
});

it('admin runs processBatch toolbar action successfully', function (): void {
    payoutAdminUser();
    payoutCreateFixture('pending');

    Livewire::test(ListPayoutTransactions::class)
        ->callAction('processBatch')
        ->assertHasNoActionErrors();
});

it('does not expose create/edit/delete abilities', function (): void {
    payoutAdminUser();
    $payoutId = payoutCreateFixture('pending');
    $record = PayoutTransactionModel::query()->findOrFail($payoutId);

    expect(PayoutTransactionResource::canCreate())->toBeFalse()
        ->and(PayoutTransactionResource::canEdit($record))->toBeFalse()
        ->and(PayoutTransactionResource::canDelete($record))->toBeFalse();
});
