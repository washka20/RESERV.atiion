<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Auth;

use App\Modules\Identity\Application\Service\JwtTokenServiceInterface;
use App\Modules\Identity\Application\Service\ParsedClaims;
use App\Modules\Identity\Application\Service\TokenPair;
use App\Modules\Identity\Domain\Exception\InvalidCredentialsException;
use App\Modules\Identity\Domain\ValueObject\UserId;
use App\Modules\Identity\Infrastructure\Persistence\Model\RefreshTokenModel;
use Lcobucci\Clock\Clock;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\PermittedFor;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Constraint\StrictValidAt;
use Ramsey\Uuid\Uuid;
use Throwable;

final class JwtTokenService implements JwtTokenServiceInterface
{
    private readonly Configuration $config;

    public function __construct(
        string $secret,
        private readonly string $issuer,
        private readonly string $audience,
        private readonly int $accessTtl,
        private readonly int $refreshTtl,
        private readonly Clock $clock,
    ) {
        $this->config = Configuration::forSymmetricSigner(
            new Sha256,
            InMemory::plainText($secret),
        );
    }

    public function issue(UserId $userId, array $extraClaims = []): TokenPair
    {
        $now = $this->clock->now();

        $builder = $this->config->builder()
            ->issuedBy($this->issuer)
            ->permittedFor($this->audience)
            ->relatedTo($userId->toString())
            ->issuedAt($now)
            ->canOnlyBeUsedAfter($now)
            ->expiresAt($now->modify("+{$this->accessTtl} seconds"))
            ->withClaim('type', 'access');

        foreach ($extraClaims as $key => $value) {
            /** @phpstan-ignore-next-line extraClaims accepts array values (memberships) */
            $builder = $builder->withClaim($key, $value);
        }

        $accessToken = $builder->getToken($this->config->signer(), $this->config->signingKey())->toString();

        $refreshPlain = bin2hex(random_bytes(32));
        RefreshTokenModel::create([
            'id' => Uuid::uuid4()->toString(),
            'user_id' => $userId->toString(),
            'token_hash' => hash('sha256', $refreshPlain),
            'expires_at' => $now->modify("+{$this->refreshTtl} seconds"),
        ]);

        return new TokenPair($accessToken, $refreshPlain, $this->accessTtl);
    }

    public function parseAccess(string $accessToken): ParsedClaims
    {
        try {
            $parsed = $this->config->parser()->parse($accessToken);
            $this->config->validator()->assert(
                $parsed,
                new SignedWith($this->config->signer(), $this->config->signingKey()),
                new StrictValidAt($this->clock),
                new IssuedBy($this->issuer),
                new PermittedFor($this->audience),
            );
        } catch (Throwable $e) {
            throw new InvalidCredentialsException('Invalid or expired token');
        }

        /** @var UnencryptedToken $parsed */
        $sub = (string) $parsed->claims()->get('sub');

        return new ParsedClaims(
            new UserId($sub),
            $parsed->claims()->all(),
        );
    }

    public function rotateRefresh(string $refreshToken): UserId
    {
        $hash = hash('sha256', $refreshToken);
        $record = RefreshTokenModel::where('token_hash', $hash)->first();

        if ($record === null || $record->revoked_at !== null || $record->expires_at < $this->clock->now()) {
            throw new InvalidCredentialsException('Invalid refresh token');
        }

        $record->update(['revoked_at' => $this->clock->now()]);

        return new UserId((string) $record->user_id);
    }

    public function refresh(string $refreshToken): TokenPair
    {
        return $this->issue($this->rotateRefresh($refreshToken));
    }

    public function revoke(string $refreshToken): void
    {
        $hash = hash('sha256', $refreshToken);
        RefreshTokenModel::where('token_hash', $hash)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => $this->clock->now()]);
    }
}
