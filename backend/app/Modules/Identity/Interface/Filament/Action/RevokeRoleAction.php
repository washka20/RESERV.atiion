<?php

declare(strict_types=1);

namespace App\Modules\Identity\Interface\Filament\Action;

use App\Modules\Identity\Application\Command\RevokeRole\RevokeRoleCommand;
use App\Modules\Identity\Domain\ValueObject\RoleName;
use App\Modules\Identity\Infrastructure\Persistence\Model\UserModel;
use App\Shared\Application\Bus\CommandBusInterface;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;

final class RevokeRoleAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'revokeRole';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Revoke role')
            ->icon('heroicon-o-minus-circle')
            ->color('danger')
            ->visible(fn (): bool => auth()->user()?->hasRole('admin') ?? false)
            ->schema([
                Select::make('role_name')
                    ->label('Role')
                    ->options(collect(RoleName::cases())->mapWithKeys(
                        fn (RoleName $r) => [$r->value => ucfirst($r->value)],
                    ))
                    ->required(),
            ])
            ->action(function (array $data, UserModel $record): void {
                $command = new RevokeRoleCommand(
                    userId: (string) $record->id,
                    roleName: RoleName::from($data['role_name']),
                );

                app(CommandBusInterface::class)->dispatch($command);

                Notification::make()
                    ->title('Role revoked')
                    ->success()
                    ->send();
            });
    }
}
