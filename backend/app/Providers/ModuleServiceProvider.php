<?php

declare(strict_types=1);

namespace App\Providers;

use App\Shared\Application\Bus\CommandBusInterface;
use App\Shared\Application\Bus\QueryBusInterface;
use App\Shared\Infrastructure\Bus\LaravelCommandBus;
use App\Shared\Infrastructure\Bus\LaravelQueryBus;
use Illuminate\Support\ServiceProvider;

final class ModuleServiceProvider extends ServiceProvider
{
    /**
     * FQCN модульных ServiceProvider'ов. Добавляется по одному при создании модуля.
     *
     * @var list<class-string<\Illuminate\Support\ServiceProvider>>
     */
    private const MODULE_PROVIDERS = [
        \App\Modules\Identity\Provider::class,
        \App\Modules\Catalog\Provider::class,
        \App\Modules\Booking\Provider::class,
        \App\Modules\Payment\Provider::class,
    ];

    public function register(): void
    {
        $this->app->singleton(CommandBusInterface::class, LaravelCommandBus::class);
        $this->app->singleton(QueryBusInterface::class, LaravelQueryBus::class);

        foreach (self::MODULE_PROVIDERS as $provider) {
            $this->app->register($provider);
        }
    }

    public function boot(): void
    {
    }
}
