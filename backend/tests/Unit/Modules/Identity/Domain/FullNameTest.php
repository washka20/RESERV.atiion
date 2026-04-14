<?php

declare(strict_types=1);

use App\Modules\Identity\Domain\ValueObject\FullName;

it('requires first and last name', function (): void {
    new FullName('', 'Doe', null);
})->throws(InvalidArgumentException::class);

it('requires last name', function (): void {
    new FullName('John', '', null);
})->throws(InvalidArgumentException::class);

it('accepts middle name as null', function (): void {
    $n = new FullName('John', 'Doe', null);
    expect($n->firstName())->toBe('John')
        ->and($n->lastName())->toBe('Doe')
        ->and($n->middleName())->toBeNull()
        ->and($n->full())->toBe('John Doe');
});

it('formats full name with middle', function (): void {
    expect((new FullName('John', 'Doe', 'William'))->full())->toBe('John William Doe');
});
