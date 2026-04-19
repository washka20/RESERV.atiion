<?php

declare(strict_types=1);

namespace App\Modules\Payment\Interface\Filament\Action;

use App\Modules\Payment\Application\Command\MarkPaymentPaid\MarkPaymentPaidCommand;
use App\Modules\Payment\Domain\ValueObject\PaymentId;
use App\Modules\Payment\Infrastructure\Persistence\Model\PaymentModel;
use App\Shared\Application\Bus\CommandBusInterface;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Throwable;

/**
 * Filament action — помечает pending платёж как оплаченный (ручной admin override).
 *
 * Доступен только для статуса PENDING. providerRef помечаем как manual-admin-<timestamp>
 * для последующего аудита. Диспатчит MarkPaymentPaidCommand через CommandBus.
 */
final class MarkPaymentPaidAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'markPaid';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Mark paid')
            ->color('success')
            ->icon('heroicon-o-check-circle')
            ->requiresConfirmation()
            ->modalHeading('Mark payment as paid')
            ->modalDescription('Это действие помечает платёж как успешно оплаченный вручную. Убедись что это корректно.')
            ->visible(fn (PaymentModel $record): bool => $record->status === 'pending')
            ->action(function (PaymentModel $record): void {
                try {
                    app(CommandBusInterface::class)->dispatch(
                        new MarkPaymentPaidCommand(
                            id: new PaymentId((string) $record->id),
                            providerRef: 'manual-admin-'.now()->timestamp,
                        ),
                    );

                    Notification::make()
                        ->title('Payment marked as paid')
                        ->success()
                        ->send();
                } catch (Throwable $e) {
                    Notification::make()
                        ->title('Cannot mark payment as paid')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
}
