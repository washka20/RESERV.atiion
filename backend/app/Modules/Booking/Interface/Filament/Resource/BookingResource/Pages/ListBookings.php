<?php

declare(strict_types=1);

namespace App\Modules\Booking\Interface\Filament\Resource\BookingResource\Pages;

use App\Modules\Booking\Interface\Filament\Resource\BookingResource;
use Filament\Resources\Pages\ListRecords;

/**
 * Страница списка бронирований. Без header actions — создание только через customer API.
 */
final class ListBookings extends ListRecords
{
    protected static string $resource = BookingResource::class;
}
