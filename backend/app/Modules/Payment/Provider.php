<?php

declare(strict_types=1);

namespace App\Modules\Payment;

use App\Modules\Booking\Domain\Event\BookingCancelled;
use App\Modules\Booking\Domain\Event\BookingCreated;
use App\Modules\Payment\Application\Command\CreatePayoutTransaction\CreatePayoutTransactionHandler;
use App\Modules\Payment\Application\Command\InitiatePayment\InitiatePaymentHandler;
use App\Modules\Payment\Application\Command\UpdatePayoutSettings\UpdatePayoutSettingsHandler;
use App\Modules\Payment\Application\Listener\ConfirmBookingOnPaymentReceived;
use App\Modules\Payment\Application\Listener\CreatePayoutTransactionOnPaymentReceived;
use App\Modules\Payment\Application\Listener\InitiatePaymentOnBookingCreated;
use App\Modules\Payment\Application\Listener\RefundPaymentOnBookingCancelled;
use App\Modules\Payment\Domain\Event\PaymentReceived;
use App\Modules\Payment\Domain\Gateway\PaymentGatewayInterface;
use App\Modules\Payment\Domain\Repository\PaymentRepositoryInterface;
use App\Modules\Payment\Domain\Repository\PayoutSettingsRepositoryInterface;
use App\Modules\Payment\Domain\Repository\PayoutTransactionRepositoryInterface;
use App\Modules\Payment\Infrastructure\Gateway\NullPaymentGateway;
use App\Modules\Payment\Infrastructure\Persistence\Repository\EloquentPaymentRepository;
use App\Modules\Payment\Infrastructure\Persistence\Repository\EloquentPayoutSettingsRepository;
use App\Modules\Payment\Infrastructure\Persistence\Repository\EloquentPayoutTransactionRepository;
use App\Modules\Payment\Infrastructure\Worker\PayoutWorker;
use App\Shared\Application\Bus\CommandBusInterface;
use App\Shared\Application\Identity\MembershipLookupInterface;
use App\Shared\Application\Outbox\OutboxPublisherInterface;
use App\Shared\Application\Transaction\TransactionManagerInterface;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\ConnectionInterface;
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
        $this->app->bind(PayoutSettingsRepositoryInterface::class, EloquentPayoutSettingsRepository::class);
        $this->app->bind(PayoutTransactionRepositoryInterface::class, EloquentPayoutTransactionRepository::class);

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

        $this->app->bind(InitiatePaymentHandler::class, static function (Application $app): InitiatePaymentHandler {
            return new InitiatePaymentHandler(
                $app->make(PaymentRepositoryInterface::class),
                $app->make(PaymentGatewayInterface::class),
                $app->make(OutboxPublisherInterface::class),
                $app->make(CommandBusInterface::class),
                $app->make(TransactionManagerInterface::class),
                feePercent: (int) config('payments.marketplace_fee_percent', 10),
            );
        });

        $this->app->bind(UpdatePayoutSettingsHandler::class, static function (Application $app): UpdatePayoutSettingsHandler {
            return new UpdatePayoutSettingsHandler(
                $app->make(PayoutSettingsRepositoryInterface::class),
                $app->make(MembershipLookupInterface::class),
                $app->make(OutboxPublisherInterface::class),
                $app->make(TransactionManagerInterface::class),
            );
        });

        $this->app->bind(CreatePayoutTransactionHandler::class, static function (Application $app): CreatePayoutTransactionHandler {
            return new CreatePayoutTransactionHandler(
                $app->make(PayoutTransactionRepositoryInterface::class),
                $app->make(OutboxPublisherInterface::class),
                $app->make(TransactionManagerInterface::class),
                feePercent: (int) config('payments.marketplace_fee_percent', 10),
            );
        });

        $this->app->bind(PayoutWorker::class, static function (Application $app): PayoutWorker {
            $channel = (string) config('payments.payouts.log_channel', 'payouts');

            return new PayoutWorker(
                $app->make(ConnectionInterface::class),
                $app->make(PayoutSettingsRepositoryInterface::class),
                $app->make(CommandBusInterface::class),
                $app->make('log')->channel($channel),
            );
        });
    }

    public function boot(Dispatcher $events): void
    {
        $events->listen(BookingCreated::class, [InitiatePaymentOnBookingCreated::class, 'handle']);
        $events->listen(PaymentReceived::class, [ConfirmBookingOnPaymentReceived::class, 'handle']);
        $events->listen(PaymentReceived::class, [CreatePayoutTransactionOnPaymentReceived::class, 'handle']);
        $events->listen(BookingCancelled::class, [RefundPaymentOnBookingCancelled::class, 'handle']);
    }
}
