<?php

declare(strict_types=1);

namespace App\Modules\Identity\Interface\Filament\Resource\MembershipResource\Pages;

use App\Modules\Identity\Interface\Filament\Resource\MembershipResource;
use Filament\Resources\Pages\ListRecords;

/**
 * Список memberships — read-only. Изменения — через public API.
 */
final class ListMemberships extends ListRecords
{
    protected static string $resource = MembershipResource::class;
}
