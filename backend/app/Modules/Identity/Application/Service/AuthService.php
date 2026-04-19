<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Service;

use App\Modules\Identity\Application\DTO\MembershipWithOrgDTO;
use App\Modules\Identity\Domain\Exception\InvalidCredentialsException;
use App\Modules\Identity\Domain\Repository\UserRepositoryInterface;
use App\Modules\Identity\Domain\Service\PasswordHasherInterface;
use App\Modules\Identity\Domain\ValueObject\Email;
use App\Modules\Identity\Domain\ValueObject\UserId;
use Throwable;

/**
 * Orchestrates auth flow: login / refresh / logout + issue JWT.
 *
 * JWT claims включают memberships (organizationId, slug, role) — fetched
 * из БД через ListUserMembershipsHandler на каждый issue. При refresh
 * memberships re-fetched — revoked access применяется сразу после rotate.
 */
final readonly class AuthService
{
    public function __construct(
        private UserRepositoryInterface $users,
        private PasswordHasherInterface $hasher,
        private JwtTokenServiceInterface $jwt,
        private UserMembershipsLookupInterface $memberships,
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

        return $this->issueWithMemberships($user->id());
    }

    public function refresh(string $refreshToken): TokenPair
    {
        $userId = $this->jwt->rotateRefresh($refreshToken);

        return $this->issueWithMemberships($userId);
    }

    public function logout(string $refreshToken): void
    {
        $this->jwt->revoke($refreshToken);
    }

    public function issueForUserId(UserId $userId): TokenPair
    {
        return $this->issueWithMemberships($userId);
    }

    /**
     * Выпускает токен и заливает свежие memberships в JWT claims.
     */
    private function issueWithMemberships(UserId $userId): TokenPair
    {
        $memberships = $this->memberships->forUser($userId->toString());

        return $this->jwt->issue($userId, [
            'memberships' => self::serializeMemberships($memberships),
        ]);
    }

    /**
     * @param  list<MembershipWithOrgDTO>  $memberships
     * @return list<array{org_id: string, org_slug: string, role: string}>
     */
    private static function serializeMemberships(array $memberships): array
    {
        $out = [];
        foreach ($memberships as $m) {
            $out[] = [
                'org_id' => $m->organizationId,
                'org_slug' => $m->organizationSlug,
                'role' => $m->role,
            ];
        }

        return $out;
    }
}
