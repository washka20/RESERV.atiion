<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Interface\Filament\Resource\CategoryResource\Pages;

use App\Modules\Catalog\Application\Command\UpdateCategory\UpdateCategoryCommand;
use App\Modules\Catalog\Interface\Filament\Resource\CategoryResource;
use App\Shared\Application\Bus\CommandBusInterface;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

final class EditCategory extends EditRecord
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $command = new UpdateCategoryCommand(
            categoryId: (string) $record->id,
            name: $data['name'],
            sortOrder: (int) ($data['sort_order'] ?? 0),
        );

        app(CommandBusInterface::class)->dispatch($command);

        return $record->refresh();
    }
}
