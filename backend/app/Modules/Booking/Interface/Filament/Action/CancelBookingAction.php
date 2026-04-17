<?php

declare(strict_types=1);

namespace App\Modules\Booking\Interface\Filament\Action;

use App\Modules\Booking\Application\Command\CancelBooking\CancelBookingCommand;
use App\Modules\Booking\Domain\Exception\CancellationNotAllowedException;
use App\Modules\Booking\Infrastructure\Persistence\Model\BookingModel;
use App\Shared\Application\Bus\CommandBusInterface;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Throwable;

/**
 * Filament action — отменяет pending/confirmed бронирование от имени admin.
 *
 * Пропускает CancellationPolicy (isAdmin=true), но всё равно ловит
 * CancellationNotAllowedException для edge cases (например, уже отменено).
 */
final class CancelBookingAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'cancel';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Cancel')
            ->color('danger')
            ->icon('heroicon-o-x-mark')
            ->requiresConfirmation()
            ->visible(
                fn (BookingModel $record): bool => in_array($record->status, ['pending', 'confirmed'], true),
            )
            ->action(function (BookingModel $record): void {
                try {
                    app(CommandBusInterface::class)->dispatch(
                        new CancelBookingCommand(
                            bookingId: (string) $record->id,
                            actorUserId: (string) auth()->id(),
                            isAdmin: true,
                        ),
                    );

                    Notification::make()
                        ->title('Booking cancelled')
                        ->success()
                        ->send();
                } catch (CancellationNotAllowedException $e) {
                    Notification::make()
                        ->title('Cannot cancel booking')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                } catch (Throwable $e) {
                    Notification::make()
                        ->title('Error cancelling booking')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
}
