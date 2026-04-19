<?php

declare(strict_types=1);

use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Modules\Catalog\Domain\ValueObject\CategoryId;
use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Identity\Domain\ValueObject\UserId;
use App\Modules\Payment\Domain\Entity\Payment;
use App\Modules\Payment\Domain\Repository\PaymentRepositoryInterface;
use App\Modules\Payment\Domain\ValueObject\PaymentId;
use App\Modules\Payment\Domain\ValueObject\PaymentMethod;
use App\Modules\Payment\Domain\ValueObject\PaymentStatus;
use App\Modules\Payment\Domain\ValueObject\Percentage;
use App\Modules\Payment\Infrastructure\Persistence\Model\PaymentModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function paymentRepo(): PaymentRepositoryInterface
{
    return app(PaymentRepositoryInterface::class);
}

function makeBookingForPayment(): BookingId
{
    $user = bookingInsertUser('payment-owner-'.uniqid().'@test.com');
    $categoryId = insertCategory('Beauty');
    $service = saveTimeSlotService('Haircut', $categoryId);
    $slotId = bookingInsertTimeSlot($service->id());
    $bookingIdStr = bookingInsertTimeSlotBooking(
        new UserId((string) $user->getAuthIdentifier()),
        $service->id(),
        $slotId,
    );

    return new BookingId($bookingIdStr);
}

it('saves new payment and finds it by id', function (): void {
    $bookingId = makeBookingForPayment();
    $paymentId = PaymentId::generate();
    $payment = Payment::initiate(
        $paymentId,
        $bookingId,
        Money::fromCents(150000, 'RUB'),
        PaymentMethod::CARD,
        Percentage::fromInt(10),
    );

    paymentRepo()->save($payment);

    $found = paymentRepo()->findById($paymentId);
    expect($found)->not->toBeNull()
        ->and($found->id()->equals($paymentId))->toBeTrue()
        ->and($found->bookingId()->equals($bookingId))->toBeTrue()
        ->and($found->gross()->amount())->toBe(150000)
        ->and($found->gross()->currency())->toBe('RUB')
        ->and($found->status())->toBe(PaymentStatus::PENDING)
        ->and($found->method())->toBe(PaymentMethod::CARD)
        ->and($found->feePercent()->value())->toBe(10)
        ->and($found->providerRef())->toBeNull()
        ->and($found->paidAt())->toBeNull();
});

it('reloads PAID payment after markPaid with providerRef and paidAt', function (): void {
    $bookingId = makeBookingForPayment();
    $paymentId = PaymentId::generate();
    $payment = Payment::initiate(
        $paymentId,
        $bookingId,
        Money::fromCents(200000, 'RUB'),
        PaymentMethod::SBP,
        Percentage::fromInt(15),
    );
    paymentRepo()->save($payment);

    $payment->markPaid('provider-ref-xyz-123');
    paymentRepo()->save($payment);

    $reloaded = paymentRepo()->findById($paymentId);
    expect($reloaded->status())->toBe(PaymentStatus::PAID)
        ->and($reloaded->providerRef())->toBe('provider-ref-xyz-123')
        ->and($reloaded->paidAt())->not->toBeNull();
});

it('persists snapshot platform_fee_cents and net_amount_cents on save', function (): void {
    $bookingId = makeBookingForPayment();
    $paymentId = PaymentId::generate();
    $payment = Payment::initiate(
        $paymentId,
        $bookingId,
        Money::fromCents(100000, 'RUB'),
        PaymentMethod::CARD,
        Percentage::fromInt(10),
    );
    paymentRepo()->save($payment);

    $row = PaymentModel::query()->find($paymentId->toString());
    expect((int) $row->platform_fee_cents)->toBe(10000)
        ->and((int) $row->net_amount_cents)->toBe(90000)
        ->and((int) $row->marketplace_fee_percent)->toBe(10)
        ->and((int) $row->amount_cents)->toBe(100000);
});

it('findByBookingId returns the payment when exists', function (): void {
    $bookingId = makeBookingForPayment();
    $paymentId = PaymentId::generate();
    $payment = Payment::initiate(
        $paymentId,
        $bookingId,
        Money::fromCents(50000, 'RUB'),
        PaymentMethod::CARD,
        Percentage::fromInt(10),
    );
    paymentRepo()->save($payment);

    $found = paymentRepo()->findByBookingId($bookingId);
    expect($found)->not->toBeNull()
        ->and($found->id()->equals($paymentId))->toBeTrue();
});

it('findByBookingId returns null when no payment for booking', function (): void {
    $missingBookingId = BookingId::generate();

    expect(paymentRepo()->findByBookingId($missingBookingId))->toBeNull();
});

it('findById returns null when payment missing', function (): void {
    expect(paymentRepo()->findById(PaymentId::generate()))->toBeNull();
});
