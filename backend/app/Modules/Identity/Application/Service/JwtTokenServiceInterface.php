<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Service;

use App\Modules\Identity\Domain\ValueObject\UserId;

interface JwtTokenServiceInterface
{
    public function issue(UserId $userId): TokenPair;

    public function refresh(string $refreshToken): TokenPair;

    public function revoke(string $refreshToken): void;

    /**
     * Возвращает UserId, если access token валиден, иначе null.
     */
    public function parseAccessToken(string $accessToken): ?UserId;
}
