<?php

declare(strict_types=1);

use App\Modules\Identity\Domain\ValueObject\OrganizationType;

it('creates from string value', function (string $value, OrganizationType $expected): void {
    expect(OrganizationType::from($value))->toBe($expected);
})->with([
    'salon' => ['salon', OrganizationType::SALON],
    'rental' => ['rental', OrganizationType::RENTAL],
    'consult' => ['consult', OrganizationType::CONSULT],
    'other' => ['other', OrganizationType::OTHER],
]);

it('rejects unknown string', function (): void {
    OrganizationType::from('unknown');
})->throws(ValueError::class);

it('has all expected cases', function (): void {
    $cases = array_map(static fn (OrganizationType $t): string => $t->value, OrganizationType::cases());
    expect($cases)->toEqual(['salon', 'rental', 'consult', 'other']);
});
