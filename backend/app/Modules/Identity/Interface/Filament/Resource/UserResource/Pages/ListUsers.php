<?php

declare(strict_types=1);

namespace App\Modules\Identity\Interface\Filament\Resource\UserResource\Pages;

use App\Modules\Identity\Interface\Filament\Resource\UserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
