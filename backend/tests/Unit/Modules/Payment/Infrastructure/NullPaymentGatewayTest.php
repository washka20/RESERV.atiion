<?php

declare(strict_types=1);

use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Payment\Domain\Gateway\GatewayChargeResult;
use App\Modules\Payment\Domain\ValueObject\PaymentMethod;
use App\Modules\Payment\Infrastructure\Gateway\NullPaymentGateway;
use Psr\Log\NullLogger;

it('createCharge always returns success with null- prefix providerRef', function () {
    $gw = new NullPaymentGateway(new NullLogger);
    $bookingId = BookingId::generate();
    $r = $gw->createCharge(Money::fromCents(10000, 'RUB'), $bookingId, PaymentMethod::CARD);
    expect($r)->toBeInstanceOf(GatewayChargeResult::class);
    expect($r->success)->toBeTrue();
    expect($r->providerRef)->toStartWith('null-');
    expect($r->errorMessage)->toBeNull();
});

it('createCharge returns unique providerRef per call', function () {
    $gw = new NullPaymentGateway(new NullLogger);
    $bookingId = BookingId::generate();
    $r1 = $gw->createCharge(Money::fromCents(10000), $bookingId, PaymentMethod::CARD);
    $r2 = $gw->createCharge(Money::fromCents(10000), $bookingId, PaymentMethod::CARD);
    expect($r1->providerRef)->not->toBe($r2->providerRef);
});

it('refund always returns success', function () {
    $gw = new NullPaymentGateway(new NullLogger);
    $r = $gw->refund('null-test-123');
    expect($r->success)->toBeTrue();
    expect($r->errorMessage)->toBeNull();
});
