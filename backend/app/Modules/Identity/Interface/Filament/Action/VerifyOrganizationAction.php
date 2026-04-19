<?php

declare(strict_types=1);

namespace App\Modules\Identity\Interface\Filament\Action;

use App\Modules\Identity\Application\Command\VerifyOrganization\VerifyOrganizationCommand;
use App\Modules\Identity\Infrastructure\Persistence\Model\OrganizationModel;
use App\Shared\Application\Bus\CommandBusInterface;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Throwable;

/**
 * Filament action — platform-level verify (KYC) организации.
 *
 * Видна только для non-verified org'ов. Диспатчит VerifyOrganizationCommand
 * через Command Bus; handler идемпотентно проставляет verified=true.
 */
final class VerifyOrganizationAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'verify';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Verify')
            ->color('success')
            ->icon('heroicon-o-check-badge')
            ->requiresConfirmation()
            ->modalHeading('Verify organization')
            ->modalDescription('Подтвердите KYC этой организации. Действие обратимо только через БД.')
            ->visible(fn (OrganizationModel $record): bool => ! $record->verified && $record->archived_at === null)
            ->action(function (OrganizationModel $record): void {
                try {
                    app(CommandBusInterface::class)->dispatch(
                        new VerifyOrganizationCommand(organizationId: (string) $record->id),
                    );

                    Notification::make()
                        ->title('Organization verified')
                        ->success()
                        ->send();
                } catch (Throwable $e) {
                    Notification::make()
                        ->title('Cannot verify organization')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
}
