<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Service;

use App\Modules\Identity\Domain\Exception\InvalidCredentialsException;
use App\Modules\Identity\Domain\Repository\UserRepositoryInterface;
use App\Modules\Identity\Domain\Service\PasswordHasherInterface;
use App\Modules\Identity\Domain\ValueObject\Email;
use Throwable;

final readonly class AuthService
{
    public function __construct(
        private UserRepositoryInterface $users,
        private PasswordHasherInterface $hasher,
        private JwtTokenServiceInterface $jwt,
    ) {}

    public function login(string $email, string $plaintext): TokenPair
    {
        try {
            $emailVO = new Email($email);
        } catch (Throwable) {
            throw new InvalidCredentialsException('Invalid credentials');
        }

        $user = $this->users->findByEmail($emailVO);
        if ($user === null) {
            throw new InvalidCredentialsException('Invalid credentials');
        }

        if (! $user->passwordHash()->matches($plaintext, $this->hasher)) {
            throw new InvalidCredentialsException('Invalid credentials');
        }

        return $this->jwt->issue($user->id());
    }

    public function refresh(string $refreshToken): TokenPair
    {
        return $this->jwt->refresh($refreshToken);
    }

    public function logout(string $refreshToken): void
    {
        $this->jwt->revoke($refreshToken);
    }
}
