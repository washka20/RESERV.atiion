<?php

declare(strict_types=1);

namespace App\Modules\Booking\Interface\Filament\Action;

use App\Modules\Booking\Application\Command\CompleteBooking\CompleteBookingCommand;
use App\Modules\Booking\Infrastructure\Persistence\Model\BookingModel;
use App\Shared\Application\Bus\CommandBusInterface;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Throwable;

/**
 * Filament action — отмечает бронирование как завершённое (CONFIRMED -> COMPLETED).
 *
 * Доступен только для статуса CONFIRMED. Диспатчит CompleteBookingCommand,
 * ошибки домена показываем через Filament notification.
 */
final class CompleteBookingAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'complete';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Complete')
            ->color('primary')
            ->icon('heroicon-o-check-badge')
            ->requiresConfirmation()
            ->visible(fn (BookingModel $record): bool => $record->status === 'confirmed')
            ->action(function (BookingModel $record): void {
                try {
                    app(CommandBusInterface::class)->dispatch(
                        new CompleteBookingCommand(bookingId: (string) $record->id),
                    );

                    Notification::make()
                        ->title('Booking completed')
                        ->success()
                        ->send();
                } catch (Throwable $e) {
                    Notification::make()
                        ->title('Cannot complete booking')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
}
