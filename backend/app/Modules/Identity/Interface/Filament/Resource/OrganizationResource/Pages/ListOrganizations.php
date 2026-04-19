<?php

declare(strict_types=1);

namespace App\Modules\Identity\Interface\Filament\Resource\OrganizationResource\Pages;

use App\Modules\Identity\Interface\Filament\Resource\OrganizationResource;
use Filament\Resources\Pages\ListRecords;

/**
 * Страница списка организаций. Без header actions — создание — через public API.
 */
final class ListOrganizations extends ListRecords
{
    protected static string $resource = OrganizationResource::class;
}
