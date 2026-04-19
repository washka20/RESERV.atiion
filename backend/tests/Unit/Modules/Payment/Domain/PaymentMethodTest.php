<?php

declare(strict_types=1);

use App\Modules\Payment\Domain\ValueObject\PaymentMethod;

it('has four method cases with expected string values', function () {
    expect(PaymentMethod::CARD->value)->toBe('card');
    expect(PaymentMethod::BANK_TRANSFER->value)->toBe('bank_transfer');
    expect(PaymentMethod::SBP->value)->toBe('sbp');
    expect(PaymentMethod::CASH->value)->toBe('cash');
});

it('returns russian labels', function () {
    expect(PaymentMethod::CARD->label())->toBe('Карта');
    expect(PaymentMethod::BANK_TRANSFER->label())->toBe('Банковский перевод');
    expect(PaymentMethod::SBP->label())->toBe('СБП');
    expect(PaymentMethod::CASH->label())->toBe('Наличные');
});
