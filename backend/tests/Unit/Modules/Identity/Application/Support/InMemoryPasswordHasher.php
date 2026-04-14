<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Identity\Application\Support;

use App\Modules\Identity\Domain\Service\PasswordHasherInterface;

final class InMemoryPasswordHasher implements PasswordHasherInterface
{
    public function hash(string $plain): string
    {
        return 'hashed:'.$plain;
    }

    public function check(string $plain, string $hash): bool
    {
        return $hash === 'hashed:'.$plain;
    }
}
