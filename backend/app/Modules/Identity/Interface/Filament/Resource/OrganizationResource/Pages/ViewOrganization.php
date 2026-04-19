<?php

declare(strict_types=1);

namespace App\Modules\Identity\Interface\Filament\Resource\OrganizationResource\Pages;

use App\Modules\Identity\Interface\Filament\Action\ArchiveOrganizationAction;
use App\Modules\Identity\Interface\Filament\Action\VerifyOrganizationAction;
use App\Modules\Identity\Interface\Filament\Resource\OrganizationResource;
use Filament\Resources\Pages\ViewRecord;

/**
 * Read-only detail страница организации. Infolist описан в OrganizationResource::infolist.
 *
 * Header actions — platform-level verify + archive (админ-гейт, не owner-gate).
 */
final class ViewOrganization extends ViewRecord
{
    protected static string $resource = OrganizationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            VerifyOrganizationAction::make(),
            ArchiveOrganizationAction::make(),
        ];
    }
}
