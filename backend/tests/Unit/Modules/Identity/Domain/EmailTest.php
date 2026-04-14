<?php

declare(strict_types=1);

use App\Modules\Identity\Domain\Exception\InvalidEmailException;
use App\Modules\Identity\Domain\ValueObject\Email;

it('accepts valid emails and normalizes to lowercase', function (string $input, string $expected): void {
    expect((new Email($input))->value())->toBe($expected);
})->with([
    ['user@example.com', 'user@example.com'],
    ['User@Example.COM', 'user@example.com'],
    ['a.b+tag@sub.example.io', 'a.b+tag@sub.example.io'],
]);

it('rejects invalid emails', function (string $input): void {
    new Email($input);
})->throws(InvalidEmailException::class)->with([
    '',
    'no-at',
    '@missing.local',
    'has space@x.com',
    'two@@at.com',
]);

it('equals another Email with same canonical value', function (): void {
    expect((new Email('A@B.COM'))->equals(new Email('a@b.com')))->toBeTrue();
});
