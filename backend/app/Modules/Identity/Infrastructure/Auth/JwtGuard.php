<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Auth;

use App\Modules\Identity\Application\Service\JwtTokenServiceInterface;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use Throwable;

final class JwtGuard implements Guard
{
    private ?Authenticatable $user = null;

    public function __construct(
        private readonly JwtTokenServiceInterface $jwt,
        private readonly UserProvider $provider,
        private readonly Request $request,
    ) {}

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function guest(): bool
    {
        return ! $this->check();
    }

    public function user(): ?Authenticatable
    {
        if ($this->user !== null) {
            return $this->user;
        }

        $token = $this->request->bearerToken();
        if ($token === null || $token === '') {
            return null;
        }

        try {
            $claims = $this->jwt->parseAccess($token);
        } catch (Throwable) {
            return null;
        }

        $this->user = $this->provider->retrieveById($claims->userId->toString());

        return $this->user;
    }

    public function id(): ?string
    {
        $user = $this->user();

        return $user?->getAuthIdentifier();
    }

    public function validate(array $credentials = []): bool
    {
        return false;
    }

    public function hasUser(): bool
    {
        return $this->user !== null;
    }

    public function setUser(Authenticatable $user): void
    {
        $this->user = $user;
    }
}
