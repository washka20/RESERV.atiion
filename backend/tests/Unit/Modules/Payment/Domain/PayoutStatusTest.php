<?php

declare(strict_types=1);

use App\Modules\Payment\Domain\ValueObject\PayoutStatus;

it('has four status cases with string values', function () {
    expect(PayoutStatus::PENDING->value)->toBe('pending');
    expect(PayoutStatus::PROCESSING->value)->toBe('processing');
    expect(PayoutStatus::PAID->value)->toBe('paid');
    expect(PayoutStatus::FAILED->value)->toBe('failed');
});

it('allows PENDING to transition to PROCESSING', function () {
    expect(PayoutStatus::PENDING->canTransitionTo(PayoutStatus::PROCESSING))->toBeTrue();
});

it('forbids PENDING to transition directly to PAID or FAILED', function () {
    expect(PayoutStatus::PENDING->canTransitionTo(PayoutStatus::PAID))->toBeFalse();
    expect(PayoutStatus::PENDING->canTransitionTo(PayoutStatus::FAILED))->toBeFalse();
});

it('allows PROCESSING to transition to PAID and FAILED', function () {
    expect(PayoutStatus::PROCESSING->canTransitionTo(PayoutStatus::PAID))->toBeTrue();
    expect(PayoutStatus::PROCESSING->canTransitionTo(PayoutStatus::FAILED))->toBeTrue();
});

it('forbids PROCESSING to transition back to PENDING', function () {
    expect(PayoutStatus::PROCESSING->canTransitionTo(PayoutStatus::PENDING))->toBeFalse();
});

it('treats PAID as terminal (no transitions allowed)', function () {
    expect(PayoutStatus::PAID->isTerminal())->toBeTrue();
    expect(PayoutStatus::PAID->canTransitionTo(PayoutStatus::PROCESSING))->toBeFalse();
    expect(PayoutStatus::PAID->canTransitionTo(PayoutStatus::FAILED))->toBeFalse();
    expect(PayoutStatus::PAID->canTransitionTo(PayoutStatus::PENDING))->toBeFalse();
});

it('treats FAILED as terminal (no transitions allowed)', function () {
    expect(PayoutStatus::FAILED->isTerminal())->toBeTrue();
    expect(PayoutStatus::FAILED->canTransitionTo(PayoutStatus::PROCESSING))->toBeFalse();
    expect(PayoutStatus::FAILED->canTransitionTo(PayoutStatus::PAID))->toBeFalse();
});

it('does not treat PENDING or PROCESSING as terminal', function () {
    expect(PayoutStatus::PENDING->isTerminal())->toBeFalse();
    expect(PayoutStatus::PROCESSING->isTerminal())->toBeFalse();
});
