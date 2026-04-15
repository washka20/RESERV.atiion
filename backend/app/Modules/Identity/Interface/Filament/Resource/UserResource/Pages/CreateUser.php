<?php

declare(strict_types=1);

namespace App\Modules\Identity\Interface\Filament\Resource\UserResource\Pages;

use App\Modules\Identity\Application\Command\RegisterUser\RegisterUserCommand;
use App\Modules\Identity\Domain\ValueObject\UserId;
use App\Modules\Identity\Infrastructure\Persistence\Model\UserModel;
use App\Modules\Identity\Interface\Filament\Resource\UserResource;
use App\Shared\Application\Bus\CommandBusInterface;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

final class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $command = new RegisterUserCommand(
            email: $data['email'],
            plaintextPassword: $data['password'],
            firstName: $data['first_name'],
            lastName: $data['last_name'],
            middleName: $data['middle_name'] ?? null,
        );

        /** @var UserId $userId */
        $userId = app(CommandBusInterface::class)->dispatch($command);

        return UserModel::query()->findOrFail($userId->toString());
    }
}
