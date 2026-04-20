<?php

declare(strict_types=1);

namespace App\Providers;

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

/**
 * Регистрирует Shared-инфраструктуру и все модульные Provider'ы.
 *
 * Модульные Provider'ы находятся автоматически через конвенцию пути
 * `app/Modules/*\/Provider.php` — новый модуль подхватится без правок этого файла.
 * Порядок детерминирован (sort) для стабильности тестов и register hook'ов.
 */
final class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CommandBusInterface::class, LaravelCommandBus::class);
        $this->app->singleton(QueryBusInterface::class, LaravelQueryBus::class);
        $this->app->singleton(DomainEventDispatcherInterface::class, LaravelDomainEventDispatcher::class);
        $this->app->singleton(TransactionManagerInterface::class, LaravelTransactionManager::class);
        $this->app->singleton(OutboxPublisherInterface::class, OutboxPublisher::class);

        foreach (self::discoverModuleProviders() as $provider) {
            $this->app->register($provider);
        }
    }

    public function boot(): void {}

    /**
     * Сканирует `app/Modules/*\/Provider.php` и возвращает FQCN найденных Provider'ов.
     *
     * @return list<class-string<ServiceProvider>>
     */
    public static function discoverModuleProviders(): array
    {
        $pattern = app_path('Modules/*/Provider.php');
        $paths = glob($pattern) ?: [];
        sort($paths);

        $providers = [];
        foreach ($paths as $path) {
            $moduleName = basename(dirname($path));
            $fqcn = "App\\Modules\\{$moduleName}\\Provider";
            if (class_exists($fqcn)) {
                $providers[] = $fqcn;
            }
        }

        /** @var list<class-string<ServiceProvider>> $providers */
        return $providers;
    }
}
