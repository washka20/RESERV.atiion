<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Interface\Filament\Resource\ServiceResource\Pages;

use App\Modules\Catalog\Application\Command\CreateService\CreateServiceCommand;
use App\Modules\Catalog\Infrastructure\Persistence\Model\ServiceModel;
use App\Modules\Catalog\Interface\Filament\Resource\ServiceResource;
use App\Shared\Application\Bus\CommandBusInterface;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

final class CreateService extends CreateRecord
{
    protected static string $resource = ServiceResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $bus = app(CommandBusInterface::class);

        $command = new CreateServiceCommand(
            name: $data['name'],
            description: $data['description'],
            priceAmount: (int) $data['price_amount'],
            priceCurrency: $data['price_currency'],
            type: $data['type'],
            categoryId: $data['category_id'],
            organizationId: (string) $data['organization_id'],
            subcategoryId: $data['subcategory_id'] ?? null,
            durationMinutes: isset($data['duration_minutes']) && $data['duration_minutes'] !== null
                ? (int) $data['duration_minutes']
                : null,
            totalQuantity: isset($data['total_quantity']) && $data['total_quantity'] !== null
                ? (int) $data['total_quantity']
                : null,
        );

        /** @var string $id */
        $id = $bus->dispatch($command);

        return ServiceModel::query()->findOrFail($id);
    }
}
