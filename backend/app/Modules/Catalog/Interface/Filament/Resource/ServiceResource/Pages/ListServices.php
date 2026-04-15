<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Interface\Filament\Resource\ServiceResource\Pages;

use App\Modules\Catalog\Interface\Filament\Resource\ServiceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListServices extends ListRecords
{
    protected static string $resource = ServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
