<?php

declare(strict_types=1);

namespace App\Modules\Identity\Interface\Api\Middleware;

use App\Modules\Identity\Application\Service\ParsedClaims;
use App\Modules\Identity\Domain\ValueObject\MembershipRole;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Проверяет, что у пользователя есть membership в organization (param route {slug})
 * и роль разрешает указанный permission.
 *
 * Использование в routes:
 * ```
 * Route::middleware(['jwt', 'org.member:settings.manage'])->patch(...);
 * ```
 *
 * Ожидает: JwtAuthMiddleware отработал раньше и положил ParsedClaims
 * в request->attributes (ключ JwtAuthMiddleware::CLAIMS_ATTRIBUTE).
 * Читает memberships claim из JWT — нет round-trip в БД, только проверка
 * токена (actor видит только те orgs, в которых он был member на момент
 * issue/refresh токена).
 *
 * Ошибки:
 * - 401 NO_JWT_CONTEXT — JWT middleware не положил claims (misconfig)
 * - 403 FORBIDDEN_NOT_MEMBER — user не member org'и
 * - 403 FORBIDDEN_INSUFFICIENT_ROLE — role не даёт permission
 */
final class MembershipGuardMiddleware
{
    public function handle(Request $request, Closure $next, string $permission): mixed
    {
        $claims = $request->attributes->get(JwtAuthMiddleware::CLAIMS_ATTRIBUTE);
        if (! $claims instanceof ParsedClaims) {
            return $this->error('NO_JWT_CONTEXT', 'JWT claims not found in request', 401);
        }

        $slug = (string) $request->route('slug');
        if ($slug === '') {
            return $this->error('FORBIDDEN_NOT_MEMBER', 'Organization slug is missing from route', 403);
        }

        $membershipRole = null;
        foreach ($claims->memberships() as $membership) {
            if ($membership['org_slug'] === $slug) {
                $membershipRole = $membership['role'];
                break;
            }
        }

        if ($membershipRole === null) {
            return $this->error('FORBIDDEN_NOT_MEMBER', 'Actor is not a member of this organization', 403);
        }

        $role = MembershipRole::tryFrom($membershipRole);
        if ($role === null || ! $role->can($permission)) {
            return $this->error('FORBIDDEN_INSUFFICIENT_ROLE', 'Role does not have permission: '.$permission, 403);
        }

        return $next($request);
    }

    private function error(string $code, string $message, int $status): JsonResponse
    {
        return response()->json([
            'success' => false,
            'data' => null,
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => null,
            ],
            'meta' => null,
        ], $status);
    }
}
