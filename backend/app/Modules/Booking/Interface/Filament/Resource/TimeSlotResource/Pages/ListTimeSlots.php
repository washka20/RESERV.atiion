<?php

declare(strict_types=1);

namespace App\Modules\Booking\Interface\Filament\Resource\TimeSlotResource\Pages;

use App\Modules\Booking\Interface\Filament\Page\GenerateTimeSlotsPage;
use App\Modules\Booking\Interface\Filament\Resource\TimeSlotResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

/**
 * Страница списка TimeSlot'ов. Создание слотов доступно через header action,
 * который ведёт на кастомную GenerateTimeSlotsPage с формой batch-генерации.
 */
final class ListTimeSlots extends ListRecords
{
    protected static string $resource = TimeSlotResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate')
                ->label('Generate Slots')
                ->icon('heroicon-o-plus-circle')
                ->color('primary')
                ->url(fn (): string => GenerateTimeSlotsPage::getUrl()),
        ];
    }
}
