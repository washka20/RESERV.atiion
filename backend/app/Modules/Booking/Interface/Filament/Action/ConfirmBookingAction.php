<?php

declare(strict_types=1);

namespace App\Modules\Booking\Interface\Filament\Action;

use App\Modules\Booking\Application\Command\ConfirmBooking\ConfirmBookingCommand;
use App\Modules\Booking\Infrastructure\Persistence\Model\BookingModel;
use App\Shared\Application\Bus\CommandBusInterface;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Throwable;

/**
 * Filament action — подтверждает pending бронирование через CommandBus.
 *
 * Доступен только для статуса PENDING. Диспатчит ConfirmBookingCommand,
 * ошибки домена показываем через Filament notification.
 */
final class ConfirmBookingAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'confirm';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Confirm')
            ->color('success')
            ->icon('heroicon-o-check')
            ->requiresConfirmation()
            ->visible(fn (BookingModel $record): bool => $record->status === 'pending')
            ->action(function (BookingModel $record): void {
                try {
                    app(CommandBusInterface::class)->dispatch(
                        new ConfirmBookingCommand(bookingId: (string) $record->id),
                    );

                    Notification::make()
                        ->title('Booking confirmed')
                        ->success()
                        ->send();
                } catch (Throwable $e) {
                    Notification::make()
                        ->title('Cannot confirm booking')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
}
