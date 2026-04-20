<?php

declare(strict_types=1);

namespace App\Modules\Platform;

use Illuminate\Support\ServiceProvider;

/**
 * Platform BC — cross-cutting infrastructure-adjacent concerns (health,
 * metrics, scheduling). Не бизнес-модуль, но живёт как модуль чтобы
 * auto-discovery его Provider подцепил.
 */
final class Provider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void {}
}
