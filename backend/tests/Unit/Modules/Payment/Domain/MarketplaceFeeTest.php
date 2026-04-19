<?php

declare(strict_types=1);

use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Payment\Domain\ValueObject\MarketplaceFee;
use App\Modules\Payment\Domain\ValueObject\Percentage;

it('calculates 10% fee from 2400 RUB', function () {
    $gross = Money::fromCents(240000, 'RUB');
    $fee = MarketplaceFee::calculate($gross, Percentage::fromInt(10));

    expect($fee->fee()->amount())->toBe(24000);
    expect($fee->fee()->currency())->toBe('RUB');
    expect($fee->net()->amount())->toBe(216000);
    expect($fee->net()->currency())->toBe('RUB');
    expect($fee->gross()->equals($gross))->toBeTrue();
});

it('uses bankers rounding: 999 cents * 10% = 100 cents (99.5 → 100 half-even)', function () {
    $gross = Money::fromCents(999, 'RUB');
    $fee = MarketplaceFee::calculate($gross, Percentage::fromInt(10));

    expect($fee->fee()->amount())->toBe(100);
    expect($fee->net()->amount())->toBe(899);
});

it('uses bankers rounding: 5 cents * 10% = 0 cents (0.5 → 0 half-even)', function () {
    $gross = Money::fromCents(5, 'RUB');
    $fee = MarketplaceFee::calculate($gross, Percentage::fromInt(10));

    expect($fee->fee()->amount())->toBe(0);
    expect($fee->net()->amount())->toBe(5);
});

it('uses bankers rounding: 15 cents * 10% = 2 cents (1.5 → 2 half-even)', function () {
    $gross = Money::fromCents(15, 'RUB');
    $fee = MarketplaceFee::calculate($gross, Percentage::fromInt(10));

    expect($fee->fee()->amount())->toBe(2);
    expect($fee->net()->amount())->toBe(13);
});

it('0% fee → fee=0, net=gross', function () {
    $gross = Money::fromCents(240000, 'RUB');
    $fee = MarketplaceFee::calculate($gross, Percentage::fromInt(0));

    expect($fee->fee()->amount())->toBe(0);
    expect($fee->net()->amount())->toBe(240000);
});

it('100% fee → fee=gross, net=0', function () {
    $gross = Money::fromCents(240000, 'RUB');
    $fee = MarketplaceFee::calculate($gross, Percentage::fromInt(100));

    expect($fee->fee()->amount())->toBe(240000);
    expect($fee->net()->amount())->toBe(0);
});

it('preserves currency USD', function () {
    $gross = Money::fromCents(10000, 'USD');
    $fee = MarketplaceFee::calculate($gross, Percentage::fromInt(15));

    expect($fee->fee()->currency())->toBe('USD');
    expect($fee->net()->currency())->toBe('USD');
    expect($fee->gross()->currency())->toBe('USD');
});
