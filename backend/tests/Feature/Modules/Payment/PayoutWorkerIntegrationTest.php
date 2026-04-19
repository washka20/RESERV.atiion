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

if (! function_exists('integrationSetupSettings')) {
    function integrationSetupSettings(int $minimumCents): OrganizationId
    {
        $orgId = insertOrganizationForTests('payout-int-org');
        $bank = new BankAccount(
            bankName: 'Тинькофф',
            accountNumber: '40702810000000004472',
            accountHolder: 'ООО «Тест»',
            bic: '044525974',
        );
        $settings = PayoutSettings::create(
            PayoutSettingsId::generate(),
            $orgId,
            $bank,
            PayoutSchedule::WEEKLY,
            Money::fromCents($minimumCents),
        );
        app(PayoutSettingsRepositoryInterface::class)->save($settings);

        return $orgId;
    }
}

if (! function_exists('integrationSetupPendingPayout')) {
    function integrationSetupPendingPayout(OrganizationId $orgId, int $netCents): PayoutTransactionId
    {
        $user = bookingInsertUser('payout-int-'.uniqid().'@test.com');
        $categoryId = insertCategory('PayoutIntCat-'.uniqid());
        $service = saveTimeSlotService('SrvInt', $categoryId, organizationId: $orgId);
        $slotId = bookingInsertTimeSlot($service->id());
        $bookingIdStr = bookingInsertTimeSlotBooking(
            new UserId((string) $user->getAuthIdentifier()),
            $service->id(),
            $slotId,
        );
        $bookingId = new BookingId($bookingIdStr);

        $paymentId = PaymentId::generate();
        $fee = (int) round($netCents * 10 / 90);
        $gross = $netCents + $fee;

        $payment = Payment::initiate(
            $paymentId,
            $bookingId,
            Money::fromCents($gross, 'RUB'),
            PaymentMethod::CARD,
            Percentage::fromInt(10),
        );
        app(PaymentRepositoryInterface::class)->save($payment);

        $payoutId = PayoutTransactionId::generate();
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
}

beforeEach(function (): void {
    // Вторник — WEEKLY schedule day
    Carbon::setTestNow(Carbon::parse('2026-04-07 10:00:00'));
});

afterEach(function (): void {
    Carbon::setTestNow();
});

it('keeps payouts pending while sum net < minimum, processes all when threshold crossed', function (): void {
    $orgId = integrationSetupSettings(minimumCents: 500_000);
    $payoutA = integrationSetupPendingPayout($orgId, netCents: 200_000);
    $payoutB = integrationSetupPendingPayout($orgId, netCents: 200_000);

    // Сумма 400_000 < minimum 500_000 → worker не трогает эти payout'ы.
    $processed = app(PayoutWorker::class)->processPending();
    expect($processed)->toBe(0);

    $repo = app(PayoutTransactionRepositoryInterface::class);
    expect($repo->findById($payoutA)->status())->toBe(PayoutStatus::PENDING);
    expect($repo->findById($payoutB)->status())->toBe(PayoutStatus::PENDING);

    // Добавили третий payout net=150_000 → суммарно 550_000 ≥ 500_000.
    $payoutC = integrationSetupPendingPayout($orgId, netCents: 150_000);

    $processedAfter = app(PayoutWorker::class)->processPending();
    expect($processedAfter)->toBe(3);

    expect($repo->findById($payoutA)->status())->toBe(PayoutStatus::PAID);
    expect($repo->findById($payoutB)->status())->toBe(PayoutStatus::PAID);
    expect($repo->findById($payoutC)->status())->toBe(PayoutStatus::PAID);
});
