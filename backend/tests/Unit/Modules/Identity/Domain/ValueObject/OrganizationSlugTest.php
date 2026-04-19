<?php

declare(strict_types=1);

use App\Modules\Identity\Domain\ValueObject\OrganizationSlug;

it('accepts valid slugs', function (string $slug): void {
    $vo = new OrganizationSlug($slug);
    expect($vo->value)->toBe($slug);
    expect((string) $vo)->toBe($slug);
})->with([
    'simple' => ['salon'],
    'with-dash' => ['salon-savvin'],
    'with-digits' => ['loft23'],
    'multiple-dashes' => ['ivan-ivanov-clinic'],
    'leading-digit' => ['23-loft'],
    'min-length' => ['abc'],
    'max-length' => [str_repeat('a', 64)],
]);

it('rejects empty string', function (): void {
    new OrganizationSlug('');
})->throws(InvalidArgumentException::class, 'длина должна быть');

it('rejects too short', function (): void {
    new OrganizationSlug('ab');
})->throws(InvalidArgumentException::class, 'длина должна быть');

it('rejects too long', function (): void {
    new OrganizationSlug(str_repeat('a', 65));
})->throws(InvalidArgumentException::class, 'длина должна быть');

it('rejects double dash', function (): void {
    new OrganizationSlug('a--b');
})->throws(InvalidArgumentException::class, 'двойные дефисы');

it('rejects starting dash', function (): void {
    new OrganizationSlug('-abc');
})->throws(InvalidArgumentException::class, '[a-z0-9-]');

it('rejects ending dash', function (): void {
    new OrganizationSlug('abc-');
})->throws(InvalidArgumentException::class, '[a-z0-9-]');

it('rejects uppercase', function (): void {
    new OrganizationSlug('Salon');
})->throws(InvalidArgumentException::class, '[a-z0-9-]');

it('rejects space', function (): void {
    new OrganizationSlug('a b c');
})->throws(InvalidArgumentException::class, '[a-z0-9-]');

it('rejects dot', function (): void {
    new OrganizationSlug('a.b');
})->throws(InvalidArgumentException::class, '[a-z0-9-]');

it('rejects cyrillic', function (): void {
    new OrganizationSlug('салон');
})->throws(InvalidArgumentException::class);

it('compares by value', function (): void {
    $a = new OrganizationSlug('salon-savvin');
    $b = new OrganizationSlug('salon-savvin');
    $c = new OrganizationSlug('loft-23');

    expect($a->equals($b))->toBeTrue();
    expect($a->equals($c))->toBeFalse();
});
