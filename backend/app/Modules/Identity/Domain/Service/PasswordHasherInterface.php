<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Service;

/**
 * Abstraction for hashing and verifying passwords.
 *
 * Implementations live in Infrastructure (e.g. BcryptPasswordHasher).
 * Kept in Domain so HashedPassword VO can depend on it without leaking framework concerns.
 */
interface PasswordHasherInterface
{
    public function hash(string $plain): string;

    public function check(string $plain, string $hash): bool;
}
