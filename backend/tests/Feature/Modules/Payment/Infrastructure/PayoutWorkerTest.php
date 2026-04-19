<?php

declare(strict_types=1);

use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Identity\Domain\ValueObject\OrganizationId;
use App\Modules\Identity\Domain\ValueObject\UserId;
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
use App\Modules\Payment\Domain\ValueObject\PayoutStatus;
use App\Modules\Payment\Domain\ValueObject\PayoutTransactionId;
use App\Modules\Payment\Domain\ValueObject\Percentage;
use App\Modules\Payment\Infrastructure\Worker\PayoutWorker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

function workerBankAccount(): BankAccount
{
    return new BankAccount(
        bankName: 'Тинькофф',
        accountNumber: '40702810000000004472',
        accountHolder: 'ООО «Тест»',
        bic: '044525974',
    );
}

/**
 * Создаёт settings c нужным расписанием + минимум. Возвращает OrganizationId.
 */
function workerSetupSettings(
    PayoutSchedule $schedule,
    int $minimumCents = 50_000,
): OrganizationId {
    $orgId = insertOrganizationForTests('worker-org');
    $settings = PayoutSettings::create(
        PayoutSettingsId::generate(),
        $orgId,
        workerBankAccount(),
        $schedule,
        Money::fromCents($minimumCents),
    );
    app(PayoutSettingsRepositoryInterface::class)->save($settings);

    return $orgId;
}

/**
 * Создаёт PENDING payout для orgId с заданным net-амаунтом. Нужен реальный
 * payment+booking (FK) — используем standard helper.
 */
function workerSetupPendingPayout(OrganizationId $orgId, int $netCents): PayoutTransactionId
{
    $user = bookingInsertUser('worker-'.uniqid().'@test.com');
    $categoryId = insertCategory('Beauty-'.uniqid());
    $service = saveTimeSlotService('Haircut', $categoryId, organizationId: $orgId);
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
        Money::fromCents($netCents + 10_000, 'RUB'),
        PaymentMethod::CARD,
        Percentage::fromInt(10),
    );
    app(PaymentRepositoryInterface::class)->save($payment);

    $payoutId = PayoutTransactionId::generate();
    $fee = (int) round($netCents * 10 / 90);
    $gross = $netCents + $fee;

    $payout = PayoutTransaction::create(
        $payoutId,
        $bookingId,
        $orgId,
        $paymentId,
        Money::fromCents($gross),
        Money::fromCents($fee),
        Money::fromCents($netCents),
    );
    app(PayoutTransactionRepositoryInterface::class)->save($payout);

    return $payoutId;
}

beforeEach(function (): void {
    // Фиксируем "сегодня" вторник (08 апреля 2026 — вторник) для детерминизма проверок schedule.
    Carbon::setTestNow(Carbon::parse('2026-04-07 10:00:00')); // 2026-04-07 вторник
});

afterEach(function (): void {
    Carbon::setTestNow();
});

it('marks payouts as paid when schedule is due and amount >= minimum', function (): void {
    $orgId = workerSetupSettings(PayoutSchedule::WEEKLY, minimumCents: 50_000);
    $payoutId = workerSetupPendingPayout($orgId, netCents: 90_000);

    $processed = app(PayoutWorker::class)->processPending();

    expect($processed)->toBe(1);
    $reloaded = app(PayoutTransactionRepositoryInterface::class)->findById($payoutId);
    expect($reloaded->status())->toBe(PayoutStatus::PAID);
});

it('skips payouts when total net is below minimum', function (): void {
    $orgId = workerSetupSettings(PayoutSchedule::WEEKLY, minimumCents: 200_000);
    $payoutId = workerSetupPendingPayout($orgId, netCents: 90_000);

    $processed = app(PayoutWorker::class)->processPending();

    expect($processed)->toBe(0);
    $reloaded = app(PayoutTransactionRepositoryInterface::class)->findById($payoutId);
    expect($reloaded->status())->toBe(PayoutStatus::PENDING);
});

it('skips organizations without payout settings', function (): void {
    $orgId = insertOrganizationForTests('no-settings-org');
    $payoutId = workerSetupPendingPayout($orgId, netCents: 90_000);

    $processed = app(PayoutWorker::class)->processPending();

    expect($processed)->toBe(0);
    $reloaded = app(PayoutTransactionRepositoryInterface::class)->findById($payoutId);
    expect($reloaded->status())->toBe(PayoutStatus::PENDING);
});

it('ignoreSchedule=true bypasses day-of-week check', function (): void {
    // Сегодня среда — WEEKLY по умолчанию не должен стрелять.
    Carbon::setTestNow(Carbon::parse('2026-04-08 10:00:00'));

    $orgId = workerSetupSettings(PayoutSchedule::WEEKLY, minimumCents: 50_000);
    $payoutId = workerSetupPendingPayout($orgId, netCents: 90_000);

    $processedNormal = app(PayoutWorker::class)->processPending();
    expect($processedNormal)->toBe(0);

    $processedForce = app(PayoutWorker::class)->processPending(ignoreSchedule: true);
    expect($processedForce)->toBe(1);

    $reloaded = app(PayoutTransactionRepositoryInterface::class)->findById($payoutId);
    expect($reloaded->status())->toBe(PayoutStatus::PAID);
});

it('ON_REQUEST schedule requires --force to process', function (): void {
    $orgId = workerSetupSettings(PayoutSchedule::ON_REQUEST, minimumCents: 50_000);
    $payoutId = workerSetupPendingPayout($orgId, netCents: 90_000);

    expect(app(PayoutWorker::class)->processPending())->toBe(0);
    expect(app(PayoutWorker::class)->processPending(ignoreSchedule: true))->toBe(1);

    $reloaded = app(PayoutTransactionRepositoryInterface::class)->findById($payoutId);
    expect($reloaded->status())->toBe(PayoutStatus::PAID);
});
