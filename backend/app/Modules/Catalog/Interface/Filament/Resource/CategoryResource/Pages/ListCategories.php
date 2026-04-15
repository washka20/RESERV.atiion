<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Interface\Filament\Resource\CategoryResource\Pages;

use App\Modules\Catalog\Interface\Filament\Resource\CategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListCategories extends ListRecords
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
