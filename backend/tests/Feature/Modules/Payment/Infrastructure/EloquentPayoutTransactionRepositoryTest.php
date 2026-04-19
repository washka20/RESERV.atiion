<?php

declare(strict_types=1);

use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Identity\Domain\ValueObject\UserId;
use App\Modules\Payment\Domain\Entity\Payment;
use App\Modules\Payment\Domain\Entity\PayoutTransaction;
use App\Modules\Payment\Domain\Repository\PaymentRepositoryInterface;
use App\Modules\Payment\Domain\Repository\PayoutTransactionRepositoryInterface;
use App\Modules\Payment\Domain\ValueObject\PaymentId;
use App\Modules\Payment\Domain\ValueObject\PaymentMethod;
use App\Modules\Payment\Domain\ValueObject\PayoutStatus;
use App\Modules\Payment\Domain\ValueObject\PayoutTransactionId;
use App\Modules\Payment\Domain\ValueObject\Percentage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function payoutTxRepo(): PayoutTransactionRepositoryInterface
{
    return app(PayoutTransactionRepositoryInterface::class);
}

/**
 * Создаёт booking + payment и возвращает ([BookingId, PaymentId, OrganizationId]).
 * Использует существующие helpers для корректных FK.
 */
function preparePaidPaymentForPayoutTest(): array
{
    $orgId = insertOrganizationForTests();
    $user = bookingInsertUser('payout-tx-'.uniqid().'@test.com');
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
        Money::fromCents(100_000, 'RUB'),
        PaymentMethod::CARD,
        Percentage::fromInt(10),
    );
    app(PaymentRepositoryInterface::class)->save($payment);

    return [$bookingId, $paymentId, $orgId];
}

it('saves payout and finds by id + booking id', function (): void {
    [$bookingId, $paymentId, $orgId] = preparePaidPaymentForPayoutTest();

    $id = PayoutTransactionId::generate();
    $payout = PayoutTransaction::create(
        $id,
        $bookingId,
        $orgId,
        $paymentId,
        Money::fromCents(100_000),
        Money::fromCents(10_000),
        Money::fromCents(90_000),
    );

    payoutTxRepo()->save($payout);

    $found = payoutTxRepo()->findById($id);
    expect($found)->not->toBeNull()
        ->and($found->id()->equals($id))->toBeTrue()
        ->and($found->bookingId()->equals($bookingId))->toBeTrue()
        ->and($found->paymentId()->equals($paymentId))->toBeTrue()
        ->and($found->status())->toBe(PayoutStatus::PENDING)
        ->and($found->grossAmount()->amount())->toBe(100_000)
        ->and($found->platformFee()->amount())->toBe(10_000)
        ->and($found->netAmount()->amount())->toBe(90_000);

    $byBooking = payoutTxRepo()->findByBookingId($bookingId);
    expect($byBooking)->not->toBeNull()
        ->and($byBooking->id()->equals($id))->toBeTrue();
});

it('persists state transitions through PROCESSING and PAID', function (): void {
    [$bookingId, $paymentId, $orgId] = preparePaidPaymentForPayoutTest();

    $id = PayoutTransactionId::generate();
    $payout = PayoutTransaction::create(
        $id,
        $bookingId,
        $orgId,
        $paymentId,
        Money::fromCents(200_000),
        Money::fromCents(20_000),
        Money::fromCents(180_000),
    );
    payoutTxRepo()->save($payout);

    $payout->moveToProcessing();
    $payout->markPaid();
    payoutTxRepo()->save($payout);

    $reloaded = payoutTxRepo()->findById($id);
    expect($reloaded->status())->toBe(PayoutStatus::PAID)
        ->and($reloaded->paidAt())->not->toBeNull();
});

it('returns null for unknown id or booking', function (): void {
    expect(payoutTxRepo()->findById(PayoutTransactionId::generate()))->toBeNull()
        ->and(payoutTxRepo()->findByBookingId(BookingId::generate()))->toBeNull();
});
