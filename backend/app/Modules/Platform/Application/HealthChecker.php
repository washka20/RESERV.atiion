<?php

declare(strict_types=1);

namespace App\Modules\Platform\Application;

use App\Modules\Platform\Domain\ValueObject\HealthStatus;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Connection;
use Throwable;

/**
 * Проверяет готовность зависимостей: Postgres, Redis cache, S3 storage.
 *
 * Postgres обязателен (без него — unhealthy). Redis и S3 — оптиональные
 * для запросов (cache miss = DB query, S3 нужен только для Media), поэтому
 * их падение даёт degraded.
 *
 * Лёгкие быстрые проверки (<10ms каждая) — endpoint должен отвечать <100ms
 * иначе load balancer посчитает его down.
 */
final readonly class HealthChecker
{
    public function __construct(
        private Connection $db,
        private CacheRepository $cache,
        private Filesystem $storage,
    ) {}

    /**
     * @return array{status: HealthStatus, checks: array<string, array{status: string, message?: string}>}
     */
    public function check(): array
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
        ];

        $status = $this->aggregateStatus($checks);

        return ['status' => $status, 'checks' => $checks];
    }

    /**
     * @return array{status: string, message?: string}
     */
    private function checkDatabase(): array
    {
        try {
            $this->db->select('SELECT 1');

            return ['status' => 'ok'];
        } catch (Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * @return array{status: string, message?: string}
     */
    private function checkCache(): array
    {
        try {
            $probe = 'health:probe:'.uniqid('', true);
            $this->cache->put($probe, '1', 5);
            $value = $this->cache->get($probe);
            $this->cache->forget($probe);

            if ($value !== '1') {
                return ['status' => 'error', 'message' => 'cache write/read mismatch'];
            }

            return ['status' => 'ok'];
        } catch (Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * @return array{status: string, message?: string}
     */
    private function checkStorage(): array
    {
        try {
            // Не пишем в bucket на каждый healthcheck — проверяем только что
            // адаптер инициализируется и отвечает на filesystem-уровне.
            $this->storage->exists('__health_probe__non_existent__');

            return ['status' => 'ok'];
        } catch (Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * @param  array<string, array{status: string, message?: string}>  $checks
     */
    private function aggregateStatus(array $checks): HealthStatus
    {
        // БД обязательна. Без неё — unhealthy.
        if (($checks['database']['status'] ?? 'error') !== 'ok') {
            return HealthStatus::UNHEALTHY;
        }

        // Остальные — degraded при падении (сервис работает, но деградированно).
        foreach (['cache', 'storage'] as $key) {
            if (($checks[$key]['status'] ?? 'error') !== 'ok') {
                return HealthStatus::DEGRADED;
            }
        }

        return HealthStatus::HEALTHY;
    }
}
