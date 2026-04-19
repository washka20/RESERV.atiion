<?php

declare(strict_types=1);

use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Identity\Domain\ValueObject\MembershipRole;
use App\Modules\Identity\Domain\ValueObject\OrganizationId;
use App\Modules\Identity\Domain\ValueObject\UserId;
use App\Modules\Identity\Infrastructure\Persistence\Model\UserModel;
use App\Modules\Payment\Domain\Entity\Payment;
use App\Modules\Payment\Domain\Entity\PayoutSettings;
use App\Modules\Payment\Domain\Entity\PayoutTransaction;
use App\Modules\Payment\Domain\Repository\PaymentRepositoryInterface;
use App\Modules\Payment\Domain\Repository\PayoutSettingsRepositoryInterface;
use App\Modules\Payment\Domain\Repository\PayoutTransactionRepositoryInterface;
use App\Modules\Payment\Domain\ValueObject\BankAccount;
use App\Modules\Payment\Domain\ValueObject\PaymentId;
use App\Modules\Payment\Domain\ValueObject\PaymentMethod;
use App\Modules\Payment\Domain\ValueObject\PayoutSchedule;
use App\Modules\Payment\Domain\ValueObject\PayoutSettingsId;
use App\Modules\Payment\Domain\ValueObject\PayoutTransactionId;
use App\Modules\Payment\Domain\ValueObject\Percentage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function payoutApiAuthHeader(UserModel $user): array
{
    return ['Authorization' => 'Bearer '.identityIssueJwt($user)];
}

function payoutApiSeedOrganization(string $slugPrefix): array
{
    $orgId = insertOrganizationForTests($slugPrefix);
    $slug = $slugPrefix.'-'.substr($orgId->toString(), 0, 8);

    return [$orgId, $slug];
}

function payoutApiSeedPayout(OrganizationId $orgId, int $grossCents = 100_000, ?string $createdAt = null): string
{
    $user = bookingInsertUser('payout-api-'.uniqid().'@test.com');
    $categoryId = insertCategory('Cat-'.uniqid());
    $service = saveTimeSlotService('Svc-'.uniqid(), $categoryId, organizationId: $orgId);
    $slotId = bookingInsertTimeSlot($service->id());
    $bookingIdStr = bookingInsertTimeSlotBooking(
        new UserId((string) $user->getAuthIdentifier()),
        $service->id(),
        $slotId,
    );
    $bookingId = new BookingId($bookingIdStr);

    $paymentId = PaymentId::generate();
    $payment = Payment::initiate(
        $paymentId,
        $bookingId,
        Money::fromCents($grossCents, 'RUB'),
        PaymentMethod::CARD,
        Percentage::fromInt(10),
    );
    app(PaymentRepositoryInterface::class)->save($payment);

    $feeCents = (int) round($grossCents * 10 / 100);
    $netCents = $grossCents - $feeCents;

    $payout = PayoutTransaction::create(
        PayoutTransactionId::generate(),
        $bookingId,
        $orgId,
        $paymentId,
        Money::fromCents($grossCents),
        Money::fromCents($feeCents),
        Money::fromCents($netCents),
    );
    app(PayoutTransactionRepositoryInterface::class)->save($payout);
    $payoutId = $payout->id()->toString();

    if ($createdAt !== null) {
        DB::table('payout_transactions')->where('id', $payoutId)->update([
            'created_at' => $createdAt,
        ]);
        DB::table('bookings')->where('id', $bookingIdStr)->update([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    return $payoutId;
}

function payoutApiSeedSettings(OrganizationId $orgId): PayoutSettings
{
    $settings = PayoutSettings::create(
        PayoutSettingsId::generate(),
        $orgId,
        new BankAccount(
            bankName: 'Sberbank',
            accountNumber: '40817810099001234472',
            accountHolder: 'ООО Ромашка',
            bic: '044525225',
        ),
        PayoutSchedule::WEEKLY,
        Money::fromCents(150_000),
    );
    app(PayoutSettingsRepositoryInterface::class)->save($settings);

    return $settings;
}

it('GET /payouts returns list for member', function (): void {
    $owner = identityInsertUser('po-list-owner@test.com');
    [$orgId, $slug] = payoutApiSeedOrganization('po-list');
    insertMembershipForTests(new UserId((string) $owner->getAuthIdentifier()), $orgId, MembershipRole::OWNER);

    payoutApiSeedPayout($orgId, 100_000);
    payoutApiSeedPayout($orgId, 200_000);
    payoutApiSeedPayout($orgId, 300_000);

    $response = $this->withHeaders(payoutApiAuthHeader($owner))
        ->getJson('/api/v1/organizations/'.$slug.'/payouts');

    $response->assertStatus(200)
        ->assertJson(['success' => true, 'error' => null])
        ->assertJsonPath('meta.total', 3)
        ->assertJsonStructure([
            'data' => [
                ['id', 'booking_id', 'gross_cents', 'platform_fee_cents', 'net_cents', 'currency', 'status', 'created_at'],
            ],
            'meta' => ['page', 'per_page', 'total', 'last_page'],
        ]);

    expect($response->json('data'))->toHaveCount(3);
});

it('GET /payouts returns 403 for non-member', function (): void {
    $outsider = identityInsertUser('po-outsider@test.com');
    [, $slug] = payoutApiSeedOrganization('po-outsider');

    $response = $this->withHeaders(payoutApiAuthHeader($outsider))
        ->getJson('/api/v1/organizations/'.$slug.'/payouts');

    $response->assertStatus(403)
        ->assertJson(['error' => ['code' => 'FORBIDDEN_NOT_MEMBER']]);
});

it('GET /payouts returns 401 without auth', function (): void {
    [, $slug] = payoutApiSeedOrganization('po-noauth');

    $response = $this->getJson('/api/v1/organizations/'.$slug.'/payouts');

    $response->assertStatus(401);
});

it('GET /payouts returns 403 for unknown slug', function (): void {
    $owner = identityInsertUser('po-unknown-owner@test.com');

    $response = $this->withHeaders(payoutApiAuthHeader($owner))
        ->getJson('/api/v1/organizations/does-not-exist/payouts');

    $response->assertStatus(403)
        ->assertJson(['error' => ['code' => 'FORBIDDEN_NOT_MEMBER']]);
});

it('GET /payout-settings returns masked bank account', function (): void {
    $owner = identityInsertUser('po-settings-owner@test.com');
    [$orgId, $slug] = payoutApiSeedOrganization('po-settings');
    insertMembershipForTests(new UserId((string) $owner->getAuthIdentifier()), $orgId, MembershipRole::OWNER);
    payoutApiSeedSettings($orgId);

    $response = $this->withHeaders(payoutApiAuthHeader($owner))
        ->getJson('/api/v1/organizations/'.$slug.'/payout-settings');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'bank_name' => 'Sberbank',
                'account_holder' => 'ООО Ромашка',
                'bic' => '044525225',
                'payout_schedule' => 'weekly',
                'minimum_payout_cents' => 150_000,
            ],
        ]);

    $masked = $response->json('data.account_number_masked');
    expect($masked)->toBe('•••• 4472')
        ->and($response->json('data'))->not->toHaveKey('account_number');
});

it('PUT /payout-settings is owner-only (staff gets 403)', function (): void {
    $staff = identityInsertUser('po-staff@test.com');
    [$orgId, $slug] = payoutApiSeedOrganization('po-staff-org');
    insertMembershipForTests(new UserId((string) $staff->getAuthIdentifier()), $orgId, MembershipRole::STAFF);

    $response = $this->withHeaders(payoutApiAuthHeader($staff))
        ->putJson('/api/v1/organizations/'.$slug.'/payout-settings', [
            'bank_name' => 'Sberbank',
            'account_number' => '40817810099001234472',
            'account_holder' => 'ООО X',
            'bic' => '044525225',
            'payout_schedule' => 'weekly',
            'minimum_payout_cents' => 100_000,
        ]);

    $response->assertStatus(403)
        ->assertJson(['error' => ['code' => 'FORBIDDEN_INSUFFICIENT_ROLE']]);
});

it('PUT /payout-settings validates BIC regex', function (): void {
    $owner = identityInsertUser('po-bic-owner@test.com');
    [$orgId, $slug] = payoutApiSeedOrganization('po-bic-org');
    insertMembershipForTests(new UserId((string) $owner->getAuthIdentifier()), $orgId, MembershipRole::OWNER);

    $response = $this->withHeaders(payoutApiAuthHeader($owner))
        ->putJson('/api/v1/organizations/'.$slug.'/payout-settings', [
            'bank_name' => 'Sberbank',
            'account_number' => '40817810099001234472',
            'account_holder' => 'ООО X',
            'bic' => '12345',
            'payout_schedule' => 'weekly',
            'minimum_payout_cents' => 100_000,
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['bic']);
});

it('PUT /payout-settings saves settings and returns masked view', function (): void {
    $owner = identityInsertUser('po-save-owner@test.com');
    [$orgId, $slug] = payoutApiSeedOrganization('po-save-org');
    insertMembershipForTests(new UserId((string) $owner->getAuthIdentifier()), $orgId, MembershipRole::OWNER);

    $response = $this->withHeaders(payoutApiAuthHeader($owner))
        ->putJson('/api/v1/organizations/'.$slug.'/payout-settings', [
            'bank_name' => 'Tinkoff',
            'account_number' => '40817810555001239999',
            'account_holder' => 'ООО Ромашка',
            'bic' => '044525974',
            'payout_schedule' => 'monthly',
            'minimum_payout_cents' => 250_000,
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'bank_name' => 'Tinkoff',
                'account_number_masked' => '•••• 9999',
                'bic' => '044525974',
                'payout_schedule' => 'monthly',
                'minimum_payout_cents' => 250_000,
            ],
        ]);

    $this->assertDatabaseHas('organization_payout_settings', [
        'organization_id' => $orgId->toString(),
        'bank_name' => 'Tinkoff',
        'bic' => '044525974',
        'payout_schedule' => 'monthly',
        'minimum_payout_cents' => 250_000,
    ]);
});

it('GET /stats aggregates last 30 days correctly', function (): void {
    $owner = identityInsertUser('po-stats-owner@test.com');
    [$orgId, $slug] = payoutApiSeedOrganization('po-stats-org');
    insertMembershipForTests(new UserId((string) $owner->getAuthIdentifier()), $orgId, MembershipRole::OWNER);

    payoutApiSeedPayout($orgId, 100_000);
    payoutApiSeedPayout($orgId, 200_000);
    payoutApiSeedPayout($orgId, 300_000);
    payoutApiSeedPayout($orgId, 999_000, createdAt: now()->subDays(45)->format('Y-m-d H:i:s'));

    $response = $this->withHeaders(payoutApiAuthHeader($owner))
        ->getJson('/api/v1/organizations/'.$slug.'/stats');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'revenue_30d_cents' => 600_000,
                'platform_fee_30d_cents' => 60_000,
                'net_payout_30d_cents' => 540_000,
                'bookings_30d' => 3,
                'currency' => 'RUB',
            ],
        ]);
});
