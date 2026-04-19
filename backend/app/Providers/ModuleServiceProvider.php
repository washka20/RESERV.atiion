<?php

declare(strict_types=1);

namespace App\Providers;

use App\Modules\Identity\Provider;
use App\Shared\Application\Bus\CommandBusInterface;
use App\Shared\Application\Bus\QueryBusInterface;
use App\Shared\Application\Event\DomainEventDispatcherInterface;
use App\Shared\Application\Outbox\OutboxPublisherInterface;
use App\Shared\Application\Transaction\TransactionManagerInterface;
use App\Shared\Infrastructure\Bus\LaravelCommandBus;
use App\Shared\Infrastructure\Bus\LaravelQueryBus;
use App\Shared\Infrastructure\Event\LaravelDomainEventDispatcher;
use App\Shared\Infrastructure\Outbox\OutboxPublisher;
use App\Shared\Infrastructure\Transaction\LaravelTransactionManager;
use Illuminate\Support\ServiceProvider;

final class ModuleServiceProvider extends ServiceProvider
{
    /**
     * FQCN модульных ServiceProvider'ов. Добавляется по одному при создании модуля.
     *
     * @var list<class-string<ServiceProvider>>
     */
    private const MODULE_PROVIDERS = [
        Provider::class,
        \App\Modules\Catalog\Provider::class,
        \App\Modules\Booking\Provider::class,
        \App\Modules\Payment\Provider::class,
    ];

    public function register(): void
    {
        $this->app->singleton(CommandBusInterface::class, LaravelCommandBus::class);
        $this->app->singleton(QueryBusInterface::class, LaravelQueryBus::class);
        $this->app->singleton(DomainEventDispatcherInterface::class, LaravelDomainEventDispatcher::class);
        $this->app->singleton(TransactionManagerInterface::class, LaravelTransactionManager::class);
        $this->app->singleton(OutboxPublisherInterface::class, OutboxPublisher::class);

        foreach (self::MODULE_PROVIDERS as $provider) {
            $this->app->register($provider);
        }
    }

    public function boot(): void {}
}
