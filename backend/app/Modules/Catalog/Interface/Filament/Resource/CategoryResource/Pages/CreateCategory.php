<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Interface\Filament\Resource\CategoryResource\Pages;

use App\Modules\Catalog\Application\Command\CreateCategory\CreateCategoryCommand;
use App\Modules\Catalog\Infrastructure\Persistence\Model\CategoryModel;
use App\Modules\Catalog\Interface\Filament\Resource\CategoryResource;
use App\Shared\Application\Bus\CommandBusInterface;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

final class CreateCategory extends CreateRecord
{
    protected static string $resource = CategoryResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $command = new CreateCategoryCommand(
            name: $data['name'],
            slug: $data['slug'],
            sortOrder: (int) ($data['sort_order'] ?? 0),
        );

        /** @var string $id */
        $id = app(CommandBusInterface::class)->dispatch($command);

        return CategoryModel::query()->findOrFail($id);
    }
}
