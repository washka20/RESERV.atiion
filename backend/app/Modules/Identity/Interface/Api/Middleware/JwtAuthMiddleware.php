<?php

declare(strict_types=1);

namespace App\Modules\Identity\Interface\Api\Middleware;

use App\Modules\Identity\Application\Service\JwtTokenServiceInterface;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

/**
 * Проверяет Bearer JWT, устанавливает user resolver и прокидывает
 * ParsedClaims в request attribute 'jwt_claims' — используется
 * MembershipGuardMiddleware для чтения org-memberships без повторного parse.
 */
final class JwtAuthMiddleware
{
    public const CLAIMS_ATTRIBUTE = 'jwt_claims';

    public function handle(Request $request, Closure $next): mixed
    {
        $token = $request->bearerToken();
        if ($token === null || $token === '') {
            return $this->unauthorized('NO_TOKEN');
        }

        try {
            $guard = Auth::guard('api');
            if (! $guard->check()) {
                return $this->unauthorized('INVALID_TOKEN');
            }
            $request->setUserResolver(static fn () => $guard->user());

            $claims = app(JwtTokenServiceInterface::class)->parseAccess($token);
            $request->attributes->set(self::CLAIMS_ATTRIBUTE, $claims);
        } catch (Throwable) {
            return $this->unauthorized('INVALID_TOKEN');
        }

        return $next($request);
    }

    private function unauthorized(string $code): JsonResponse
    {
        return response()->json([
            'success' => false,
            'data' => null,
            'error' => [
                'code' => $code,
                'message' => 'Unauthorized',
                'details' => null,
            ],
            'meta' => null,
        ], 401);
    }
}
