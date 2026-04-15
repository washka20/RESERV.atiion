<?php

declare(strict_types=1);

namespace App\Modules\Identity\Interface\Filament\Resource\UserResource\Pages;

use App\Modules\Identity\Application\Command\UpdateUser\UpdateUserCommand;
use App\Modules\Identity\Interface\Filament\Resource\UserResource;
use App\Shared\Application\Bus\CommandBusInterface;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

final class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $command = new UpdateUserCommand(
            userId: (string) $record->id,
            email: $data['email'] ?? null,
            firstName: $data['first_name'] ?? null,
            lastName: $data['last_name'] ?? null,
            middleName: $data['middle_name'] ?? null,
        );

        app(CommandBusInterface::class)->dispatch($command);

        return $record->refresh();
    }
}
