<?php

declare(strict_types=1);

namespace App\Modules\Identity\Interface\Filament\Action;

use App\Modules\Identity\Application\Command\AdminArchiveOrganization\AdminArchiveOrganizationCommand;
use App\Modules\Identity\Infrastructure\Persistence\Model\OrganizationModel;
use App\Shared\Application\Bus\CommandBusInterface;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Throwable;

/**
 * Filament action — platform-level архивация организации.
 *
 * Диспатчит AdminArchiveOrganizationCommand (минует owner-gate
 * ArchiveOrganizationCommand'а — авторизация выполняется Filament'ом
 * через canViewAny/canArchive). Идемпотентно: повторный archive soft-ок.
 */
final class ArchiveOrganizationAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'archive';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Archive')
            ->color('danger')
            ->icon('heroicon-o-archive-box-x-mark')
            ->requiresConfirmation()
            ->modalHeading('Archive organization')
            ->modalDescription('Организация будет помечена как archived. Services внутри останутся в БД, но станут невидимы каталогу.')
            ->visible(fn (OrganizationModel $record): bool => $record->archived_at === null)
            ->action(function (OrganizationModel $record): void {
                try {
                    app(CommandBusInterface::class)->dispatch(
                        new AdminArchiveOrganizationCommand(organizationId: (string) $record->id),
                    );

                    Notification::make()
                        ->title('Organization archived')
                        ->success()
                        ->send();
                } catch (Throwable $e) {
                    Notification::make()
                        ->title('Cannot archive organization')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
}
