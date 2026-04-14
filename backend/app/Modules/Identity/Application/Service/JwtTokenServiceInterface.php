<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Service;

use App\Modules\Identity\Domain\ValueObject\UserId;

interface JwtTokenServiceInterface
{
    /**
     * @param  array<string, scalar>  $extraClaims
     */
    public function issue(UserId $userId, array $extraClaims = []): TokenPair;

    /**
     * @throws \App\Modules\Identity\Domain\Exception\InvalidCredentialsException
     */
    public function parseAccess(string $accessToken): ParsedClaims;

    public function refresh(string $refreshToken): TokenPair;

    public function revoke(string $refreshToken): void;
}
