<?php

declare(strict_types=1);

use App\Modules\Identity\Domain\Service\PasswordHasherInterface;
use App\Modules\Identity\Domain\ValueObject\HashedPassword;

it('wraps a non-empty hash string', function (): void {
    $hp = new HashedPassword('$2y$12$abcdef...');
    expect($hp->value())->toBe('$2y$12$abcdef...');
});

it('rejects empty hash', function (): void {
    new HashedPassword('');
})->throws(InvalidArgumentException::class);

it('creates from plaintext via hasher', function (): void {
    $hasher = new class implements PasswordHasherInterface
    {
        public function hash(string $plain): string
        {
            return 'HASHED:'.$plain;
        }

        public function check(string $plain, string $hash): bool
        {
            return $hash === 'HASHED:'.$plain;
        }
    };

    $hp = HashedPassword::fromPlaintext('secret', $hasher);
    expect($hp->value())->toBe('HASHED:secret');
});

it('matches plaintext via hasher', function (): void {
    $hasher = new class implements PasswordHasherInterface
    {
        public function hash(string $plain): string
        {
            return 'HASHED:'.$plain;
        }

        public function check(string $plain, string $hash): bool
        {
            return $hash === 'HASHED:'.$plain;
        }
    };

    $hp = new HashedPassword('HASHED:secret');
    expect($hp->matches('secret', $hasher))->toBeTrue();
    expect($hp->matches('wrong', $hasher))->toBeFalse();
});
