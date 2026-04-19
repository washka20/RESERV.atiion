<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Service;

use App\Modules\Identity\Domain\Exception\InvalidCredentialsException;
use App\Modules\Identity\Domain\ValueObject\UserId;

interface JwtTokenServiceInterface
{
    /**
     * Выпускает access + refresh токены.
     *
     * $extraClaims — дополнительные claims в payload, например
     * 'memberships' => list<array{org_id, org_slug, role}> для
     * organization context (см. AuthService).
     *
     * @param  array<string, mixed>  $extraClaims
     */
    public function issue(UserId $userId, array $extraClaims = []): TokenPair;

    /**
     * @throws InvalidCredentialsException
     */
    public function parseAccess(string $accessToken): ParsedClaims;

    /**
     * Rotate refresh: валидирует, revoke'ит старый и возвращает UserId владельца.
     * Вызывающий код (AuthService) сам решает какие extraClaims поместить в новый
     * access токен — так refresh получает свежие memberships из БД.
     *
     * @throws InvalidCredentialsException
     */
    public function rotateRefresh(string $refreshToken): UserId;

    public function refresh(string $refreshToken): TokenPair;

    public function revoke(string $refreshToken): void;
}
