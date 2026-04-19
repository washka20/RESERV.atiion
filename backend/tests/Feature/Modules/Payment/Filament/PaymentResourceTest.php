<?php

declare(strict_types=1);

use App\Modules\Identity\Domain\ValueObject\UserId;
use App\Modules\Identity\Infrastructure\Persistence\Model\UserModel;
use App\Modules\Payment\Infrastructure\Persistence\Model\PaymentModel;
use App\Modules\Payment\Interface\Filament\Resource\PaymentResource;
use App\Modules\Payment\Interface\Filament\Resource\PaymentResource\Pages\ListPayments;
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
function paymentAdminUser(): UserModel
{
    $admin = UserModel::factory()->create();
    $admin->assignRole('admin');
    actingAs($admin, 'web');

    return $admin;
}

/*
 * Создаёт фиктивное бронирование + платёж в БД напрямую (обходит domain).
 * Возвращает bookingId.
 *
 * @return array{0:string,1:string} [$bookingId, $paymentId]
 */
function paymentCreateFixture(string $status = 'pending'): array
{
    $user = bookingInsertUser('payment-fx-'.Str::random(6).'@test.com');
    $orgId = insertOrganizationForTests('payment-fx');
    $categoryId = insertCategory('PaymentFx'.Str::random(4));
    $service = saveTimeSlotService('Fx svc '.Str::random(4), $categoryId, organizationId: $orgId);
    $slotId = bookingInsertTimeSlot($service->id());
    $bookingId = bookingInsertTimeSlotBooking(
        new UserId((string) $user->getAuthIdentifier()),
        $service->id(),
        $slotId,
    );

    $paymentId = (string) Str::uuid();
    DB::table('payments')->insert([
        'id' => $paymentId,
        'booking_id' => $bookingId,
        'amount_cents' => 240_000,
        'currency' => 'RUB',
        'status' => $status,
        'method' => 'card',
        'provider_ref' => $status === 'paid' ? 'existing-ref' : null,
        'marketplace_fee_percent' => 10,
        'platform_fee_cents' => 24_000,
        'net_amount_cents' => 216_000,
        'paid_at' => $status === 'paid' ? now() : null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return [$bookingId, $paymentId];
}

it('admin sees payments list page', function (): void {
    paymentAdminUser();

    $this->get(PaymentResource::getUrl('index'))->assertOk();
});

it('manager also sees payments list page', function (): void {
    $manager = UserModel::factory()->create();
    $manager->assignRole('manager');
    actingAs($manager, 'web');

    $this->get(PaymentResource::getUrl('index'))->assertOk();
});

it('customer cannot access payments admin page', function (): void {
    $customer = UserModel::factory()->create();
    $customer->assignRole('customer');
    actingAs($customer, 'web');

    $this->get(PaymentResource::getUrl('index'))->assertForbidden();
});

it('admin marks pending payment as paid via row action', function (): void {
    paymentAdminUser();
    [, $paymentId] = paymentCreateFixture('pending');

    $record = PaymentModel::query()->findOrFail($paymentId);

    Livewire::test(ListPayments::class)
        ->callTableAction('markPaid', $record)
        ->assertHasNoTableActionErrors();

    $fresh = PaymentModel::query()->findOrFail($paymentId);
    expect($fresh->status)->toBe('paid')
        ->and($fresh->provider_ref)->toStartWith('manual-admin-')
        ->and($fresh->paid_at)->not->toBeNull();
});

it('admin refunds paid payment via row action', function (): void {
    paymentAdminUser();
    [, $paymentId] = paymentCreateFixture('paid');

    $record = PaymentModel::query()->findOrFail($paymentId);

    Livewire::test(ListPayments::class)
        ->callTableAction('refund', $record)
        ->assertHasNoTableActionErrors();

    $fresh = PaymentModel::query()->findOrFail($paymentId);
    expect($fresh->status)->toBe('refunded');
});

it('markPaid action hidden for non-pending payment', function (): void {
    paymentAdminUser();
    [, $paymentId] = paymentCreateFixture('paid');

    $record = PaymentModel::query()->findOrFail($paymentId);

    Livewire::test(ListPayments::class)
        ->assertTableActionHidden('markPaid', $record);
});

it('refund action hidden for non-paid payment', function (): void {
    paymentAdminUser();
    [, $paymentId] = paymentCreateFixture('pending');

    $record = PaymentModel::query()->findOrFail($paymentId);

    Livewire::test(ListPayments::class)
        ->assertTableActionHidden('refund', $record);
});

it('does not expose create/edit/delete abilities', function (): void {
    paymentAdminUser();
    [, $paymentId] = paymentCreateFixture('pending');
    $record = PaymentModel::query()->findOrFail($paymentId);

    expect(PaymentResource::canCreate())->toBeFalse()
        ->and(PaymentResource::canEdit($record))->toBeFalse()
        ->and(PaymentResource::canDelete($record))->toBeFalse();
});
