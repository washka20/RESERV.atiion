<?php

declare(strict_types=1);

namespace App\Modules\Payment\Interface\Filament\Action;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Artisan;
use Throwable;

/**
 * Toolbar action — запускает batch обработку pending payouts (app:payouts:process --force).
 *
 * Обходит расписание выплат и force-инициирует процесс для всех pending payouts,
 * которые достигли минимума. Используется admin для ручного ускорения выплат.
 */
final class ProcessPayoutBatchAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'processBatch';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Process payouts now')
            ->color('primary')
            ->icon('heroicon-o-play')
            ->requiresConfirmation()
            ->modalHeading('Force process payout batch')
            ->modalDescription('Запустит app:payouts:process --force для всех pending payouts.')
            ->action(function (): void {
                try {
                    $exit = Artisan::call('app:payouts:process', ['--force' => true]);

                    if ($exit !== 0) {
                        Notification::make()
                            ->title('Payout batch failed')
                            ->body(sprintf('Command exited with code %d', $exit))
                            ->danger()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('Payout batch processed')
                        ->success()
                        ->send();
                } catch (Throwable $e) {
                    Notification::make()
                        ->title('Payout batch error')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
}
