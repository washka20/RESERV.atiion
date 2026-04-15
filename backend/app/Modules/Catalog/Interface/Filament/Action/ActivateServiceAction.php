<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Interface\Filament\Action;

use App\Modules\Catalog\Application\Command\ActivateService\ActivateServiceCommand;
use App\Modules\Catalog\Infrastructure\Persistence\Model\ServiceModel;
use App\Shared\Application\Bus\CommandBusInterface;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

/**
 * Action активации услуги через ActivateServiceCommand.
 */
final class ActivateServiceAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'activate';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Активировать')
            ->icon('heroicon-o-eye')
            ->color('success')
            ->requiresConfirmation()
            ->visible(fn (): bool => auth()->user()?->hasAnyRole(['admin', 'manager']) ?? false)
            ->action(function (ServiceModel $record): void {
                app(CommandBusInterface::class)->dispatch(
                    new ActivateServiceCommand(serviceId: (string) $record->id),
                );

                Notification::make()
                    ->title('Услуга активирована')
                    ->success()
                    ->send();
            });
    }
}
