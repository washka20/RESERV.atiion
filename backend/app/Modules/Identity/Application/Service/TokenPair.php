<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Service;

final readonly class TokenPair
{
    public function __construct(
        public string $accessToken,
        public string $refreshToken,
        public int $expiresIn,
    ) {}
}
