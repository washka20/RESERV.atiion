<?php

declare(strict_types=1);

use App\Modules\Payment\Domain\ValueObject\BankAccount;

it('creates BankAccount with valid fields', function (): void {
    $account = new BankAccount(
        bankName: 'Тинькофф',
        accountNumber: '40702810000000004472',
        accountHolder: 'ООО «Ромашка»',
        bic: '044525974',
    );

    expect($account->bankName)->toBe('Тинькофф')
        ->and($account->accountNumber)->toBe('40702810000000004472')
        ->and($account->accountHolder)->toBe('ООО «Ромашка»')
        ->and($account->bic)->toBe('044525974');
});

it('rejects account number shorter than 10 chars', function (): void {
    new BankAccount(
        bankName: 'Тинькофф',
        accountNumber: '123456789',
        accountHolder: 'ООО «Ромашка»',
        bic: '044525974',
    );
})->throws(InvalidArgumentException::class, 'account number too short');

it('rejects BIC not exactly 9 digits', function (string $bic): void {
    new BankAccount(
        bankName: 'Тинькофф',
        accountNumber: '40702810000000004472',
        accountHolder: 'ООО «Ромашка»',
        bic: $bic,
    );
})
    ->with([
        'too short' => ['12345678'],
        'too long' => ['1234567890'],
        'with letters' => ['04452597A'],
        'empty' => [''],
    ])
    ->throws(InvalidArgumentException::class, 'BIC must be 9 digits');

it('returns masked account with last 4 digits', function (): void {
    $account = new BankAccount(
        bankName: 'Тинькофф',
        accountNumber: '40702810000000004472',
        accountHolder: 'ООО «Ромашка»',
        bic: '044525974',
    );

    expect($account->masked())->toBe('•••• 4472');
});

it('equals is true for same fields and false otherwise', function (): void {
    $a = new BankAccount('Тинькофф', '40702810000000004472', 'ООО «Ромашка»', '044525974');
    $b = new BankAccount('Тинькофф', '40702810000000004472', 'ООО «Ромашка»', '044525974');
    $c = new BankAccount('Сбербанк', '40702810000000004472', 'ООО «Ромашка»', '044525974');

    expect($a->equals($b))->toBeTrue()
        ->and($a->equals($c))->toBeFalse();
});
