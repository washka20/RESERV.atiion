<?php

declare(strict_types=1);

namespace App\Modules\Platform\Domain\ValueObject;

/**
 * Статус проверки зависимостей системы.
 *
 * `healthy` — всё работает, load balancer может слать трафик.
 * `degraded` — часть dependencies недоступна, но сервис ещё принимает запросы
 *   (например Redis лёг, но БД отвечает). HTTP 200.
 * `unhealthy` — критические deps недоступны, сервис не готов к трафику. HTTP 503.
 */
enum HealthStatus: string
{
    case HEALTHY = 'healthy';
    case DEGRADED = 'degraded';
    case UNHEALTHY = 'unhealthy';

    public function httpStatus(): int
    {
        return $this === self::UNHEALTHY ? 503 : 200;
    }
}
