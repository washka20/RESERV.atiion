<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * Регистрирует named rate limiters (ADR-018). В testing env все лимиты
     * сняты — параллельные тесты иначе забивают quotas и начинают получать
     * 429 вместо ожидаемого response.
     */
    public function boot(): void
    {
        $testing = $this->app->environment('testing');

        RateLimiter::for('auth-register', function (Request $request) use ($testing): Limit {
            return $testing
                ? Limit::none()
                : Limit::perMinute(3)->by($request->ip());
        });

        RateLimiter::for('auth-login', function (Request $request) use ($testing): Limit {
            return $testing
                ? Limit::none()
                : Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('auth-refresh', function (Request $request) use ($testing): Limit {
            return $testing
                ? Limit::none()
                : Limit::perMinute(20)->by($request->ip());
        });

        RateLimiter::for('auth-me-write', function (Request $request) use ($testing): Limit {
            return $testing
                ? Limit::none()
                : Limit::perMinute(10)->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip()));
        });

        RateLimiter::for('booking-write', function (Request $request) use ($testing): Limit {
            return $testing
                ? Limit::none()
                : Limit::perMinute(10)->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip()));
        });
    }
}
