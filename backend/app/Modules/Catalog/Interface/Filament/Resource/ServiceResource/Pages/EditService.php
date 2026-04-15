<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Interface\Filament\Resource\ServiceResource\Pages;

use App\Modules\Catalog\Application\Command\UpdateService\UpdateServiceCommand;
use App\Modules\Catalog\Interface\Filament\Resource\ServiceResource;
use App\Shared\Application\Bus\CommandBusInterface;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

final class EditService extends EditRecord
{
    protected static string $resource = ServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $command = new UpdateServiceCommand(
            serviceId: (string) $record->id,
            name: $data['name'],
            description: $data['description'],
            priceAmount: (int) $data['price_amount'],
            priceCurrency: $data['price_currency'],
        );

        app(CommandBusInterface::class)->dispatch($command);

        return $record->refresh();
    }
}
