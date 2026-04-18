<?php

declare(strict_types=1);

namespace App\Modules\Booking\Interface\Filament\Resource\TimeSlotResource\Pages;

use App\Modules\Booking\Interface\Filament\Resource\TimeSlotResource;
use Filament\Resources\Pages\ViewRecord;

/**
 * Read-only detail страница слота. Infolist описан в TimeSlotResource::infolist.
 */
final class ViewTimeSlot extends ViewRecord
{
    protected static string $resource = TimeSlotResource::class;
}
