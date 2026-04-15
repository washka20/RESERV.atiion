<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Interface\Filament\Action;

use App\Modules\Catalog\Application\Command\DeactivateService\DeactivateServiceCommand;
use App\Modules\Catalog\Infrastructure\Persistence\Model\ServiceModel;
use App\Shared\Application\Bus\CommandBusInterface;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

/**
 * Action деактивации услуги через DeactivateServiceCommand.
 */
final class DeactivateServiceAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'deactivate';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Деактивировать')
            ->icon('heroicon-o-eye-slash')
            ->color('warning')
            ->requiresConfirmation()
            ->visible(fn (): bool => auth()->user()?->hasAnyRole(['admin', 'manager']) ?? false)
            ->action(function (ServiceModel $record): void {
                app(CommandBusInterface::class)->dispatch(
                    new DeactivateServiceCommand(serviceId: (string) $record->id),
                );

                Notification::make()
                    ->title('Услуга деактивирована')
                    ->success()
                    ->send();
            });
    }
}
