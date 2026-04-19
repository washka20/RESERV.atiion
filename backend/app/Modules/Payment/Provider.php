<?php

declare(strict_types=1);

namespace App\Modules\Payment;

use App\Modules\Payment\Domain\Gateway\PaymentGatewayInterface;
use App\Modules\Payment\Domain\Repository\PaymentRepositoryInterface;
use App\Modules\Payment\Infrastructure\Gateway\NullPaymentGateway;
use App\Modules\Payment\Infrastructure\Persistence\Repository\EloquentPaymentRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

/**
 * Payment BC service provider.
 *
 * - Биндит PaymentRepositoryInterface на Eloquent реализацию.
 * - Разрешает PaymentGatewayInterface через driver из config('payments.default_gateway').
 * - Команды/запросы разрешаются по соглашению XxxCommand → XxxHandler через LaravelCommandBus.
 */
final class Provider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PaymentRepositoryInterface::class, EloquentPaymentRepository::class);

        $this->app->bind(PaymentGatewayInterface::class, static function (Application $app): PaymentGatewayInterface {
            $driver = (string) config('payments.default_gateway', 'null');
            $gateways = (array) config('payments.gateways', []);
            $class = $gateways[$driver] ?? null;

            if (! is_string($class) || ! class_exists($class)) {
                throw new RuntimeException(sprintf('Payment gateway driver "%s" not configured', $driver));
            }

            return $app->make($class);
        });

        $this->app->bind(NullPaymentGateway::class, static function (Application $app): NullPaymentGateway {
            $channel = (string) config('payments.log_channel', 'payments');

            return new NullPaymentGateway($app->make('log')->channel($channel));
        });
    }

    public function boot(): void {}
}
