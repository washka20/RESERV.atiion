<?php

declare(strict_types=1);

use App\Modules\Payment\Domain\ValueObject\PaymentStatus;

it('has four status cases with string values', function () {
    expect(PaymentStatus::PENDING->value)->toBe('pending');
    expect(PaymentStatus::PAID->value)->toBe('paid');
    expect(PaymentStatus::REFUNDED->value)->toBe('refunded');
    expect(PaymentStatus::FAILED->value)->toBe('failed');
});

it('allows PENDING to transition to PAID and FAILED', function () {
    expect(PaymentStatus::PENDING->canTransitionTo(PaymentStatus::PAID))->toBeTrue();
    expect(PaymentStatus::PENDING->canTransitionTo(PaymentStatus::FAILED))->toBeTrue();
});

it('forbids PENDING to transition to REFUNDED', function () {
    expect(PaymentStatus::PENDING->canTransitionTo(PaymentStatus::REFUNDED))->toBeFalse();
});

it('allows PAID to transition to REFUNDED', function () {
    expect(PaymentStatus::PAID->canTransitionTo(PaymentStatus::REFUNDED))->toBeTrue();
});

it('forbids PAID to transition to PENDING or FAILED', function () {
    expect(PaymentStatus::PAID->canTransitionTo(PaymentStatus::PENDING))->toBeFalse();
    expect(PaymentStatus::PAID->canTransitionTo(PaymentStatus::FAILED))->toBeFalse();
});

it('treats REFUNDED as terminal (no transitions allowed)', function () {
    expect(PaymentStatus::REFUNDED->isTerminal())->toBeTrue();
    expect(PaymentStatus::REFUNDED->canTransitionTo(PaymentStatus::PAID))->toBeFalse();
    expect(PaymentStatus::REFUNDED->canTransitionTo(PaymentStatus::PENDING))->toBeFalse();
});

it('treats FAILED as terminal (no transitions allowed)', function () {
    expect(PaymentStatus::FAILED->isTerminal())->toBeTrue();
    expect(PaymentStatus::FAILED->canTransitionTo(PaymentStatus::PAID))->toBeFalse();
});

it('does not treat PENDING or PAID as terminal', function () {
    expect(PaymentStatus::PENDING->isTerminal())->toBeFalse();
    expect(PaymentStatus::PAID->isTerminal())->toBeFalse();
});
