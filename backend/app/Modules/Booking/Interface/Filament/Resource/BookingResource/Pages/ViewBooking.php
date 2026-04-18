<?php

declare(strict_types=1);

namespace App\Modules\Booking\Interface\Filament\Resource\BookingResource\Pages;

use App\Modules\Booking\Interface\Filament\Action\CancelBookingAction;
use App\Modules\Booking\Interface\Filament\Action\CompleteBookingAction;
use App\Modules\Booking\Interface\Filament\Action\ConfirmBookingAction;
use App\Modules\Booking\Interface\Filament\Resource\BookingResource;
use Filament\Resources\Pages\ViewRecord;

/**
 * Read-only detail страница бронирования. Infolist описан в BookingResource::infolist.
 *
 * В header'е — actions для state-переходов (confirm/complete/cancel).
 */
final class ViewBooking extends ViewRecord
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ConfirmBookingAction::make(),
            CompleteBookingAction::make(),
            CancelBookingAction::make(),
        ];
    }
}
