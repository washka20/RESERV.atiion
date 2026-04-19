<?php

declare(strict_types=1);

namespace App\Modules\Payment\Interface\Filament\Action;

use App\Modules\Payment\Application\Command\RefundPayment\RefundPaymentCommand;
use App\Modules\Payment\Domain\ValueObject\PaymentId;
use App\Modules\Payment\Infrastructure\Persistence\Model\PaymentModel;
use App\Shared\Application\Bus\CommandBusInterface;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Throwable;

/**
 * Filament action — инициирует возврат оплаченного платежа.
 *
 * Доступен только для статуса PAID. Domain-слой публикует PaymentRefunded reliable,
 * Payouts BC откатит выплату.
 */
final class RefundPaymentAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'refund';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Refund')
            ->color('warning')
            ->icon('heroicon-o-arrow-uturn-left')
            ->requiresConfirmation()
            ->modalHeading('Refund payment')
            ->modalDescription('Платёж будет возвращён. Payout по этому бронированию также будет откачен.')
            ->visible(fn (PaymentModel $record): bool => $record->status === 'paid')
            ->action(function (PaymentModel $record): void {
                try {
                    app(CommandBusInterface::class)->dispatch(
                        new RefundPaymentCommand(
                            id: new PaymentId((string) $record->id),
                        ),
                    );

                    Notification::make()
                        ->title('Payment refunded')
                        ->success()
                        ->send();
                } catch (Throwable $e) {
                    Notification::make()
                        ->title('Cannot refund payment')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
}
