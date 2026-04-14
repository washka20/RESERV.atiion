<?php

declare(strict_types=1);

namespace App\Modules\Identity\Interface\Api\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

final class JwtAuthMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        if ($request->bearerToken() === null || $request->bearerToken() === '') {
            return $this->unauthorized('NO_TOKEN');
        }

        try {
            $guard = Auth::guard('api');
            if (! $guard->check()) {
                return $this->unauthorized('INVALID_TOKEN');
            }
            $request->setUserResolver(static fn () => $guard->user());
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
