<?php

declare(strict_types=1);

namespace App\Modules\Platform\Interface\Api;

use App\Modules\Platform\Application\HealthChecker;
use Illuminate\Http\JsonResponse;

/**
 * GET /api/v1/health — endpoint для load balancer / k8s liveness probe.
 *
 * Without auth (балансер должен уметь ping'овать без токена).
 * HTTP 200 для healthy+degraded, HTTP 503 для unhealthy.
 */
final readonly class HealthController
{
    public function __construct(
        private HealthChecker $checker,
    ) {}

    public function __invoke(): JsonResponse
    {
        $result = $this->checker->check();

        return response()->json([
            'status' => $result['status']->value,
            'checks' => $result['checks'],
        ], $result['status']->httpStatus());
    }
}
