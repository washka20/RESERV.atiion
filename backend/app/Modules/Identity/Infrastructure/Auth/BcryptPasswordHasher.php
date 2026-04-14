<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Auth;

use App\Modules\Identity\Domain\Service\PasswordHasherInterface;
use Illuminate\Support\Facades\Hash;

final class BcryptPasswordHasher implements PasswordHasherInterface
{
    public function hash(string $plain): string
    {
        return Hash::make($plain);
    }

    public function check(string $plain, string $hash): bool
    {
        return Hash::check($plain, $hash);
    }
}
