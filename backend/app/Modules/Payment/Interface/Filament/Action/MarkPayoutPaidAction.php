<?php

declare(strict_types=1);

namespace App\Modules\Payment\Interface\Filament\Action;

use App\Modules\Payment\Application\Command\MarkPayoutPaid\MarkPayoutPaidCommand;
use App\Modules\Payment\Infrastructure\Persistence\Model\PayoutTransactionModel;
use App\Shared\Application\Bus\CommandBusInterface;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Throwable;

/**
 * Filament action — помечает payout как выплаченный вручную.
 *
 * Доступен для статусов PENDING/PROCESSING. Handler переводит PENDING → PROCESSING → PAID
 * и публикует PayoutMarkedPaid reliable.
 */
final class MarkPayoutPaidAction extends Action
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
            ->icon('heroicon-o-banknotes')
            ->requiresConfirmation()
            ->modalHeading('Mark payout as paid')
            ->modalDescription('Помечает выплату как осуществлённую провайдером.')
            ->visible(fn (PayoutTransactionModel $record): bool => in_array($record->status, ['pending', 'processing'], true))
            ->action(function (PayoutTransactionModel $record): void {
                try {
                    app(CommandBusInterface::class)->dispatch(
                        new MarkPayoutPaidCommand(payoutId: (string) $record->id),
                    );

                    Notification::make()
                        ->title('Payout marked as paid')
                        ->success()
                        ->send();
                } catch (Throwable $e) {
                    Notification::make()
                        ->title('Cannot mark payout as paid')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
}
