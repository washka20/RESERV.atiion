<?php

declare(strict_types=1);

namespace App\Modules\Identity\Interface\Filament\Listener;

use App\Modules\Identity\Domain\Event\UserRoleAssigned;
use App\Modules\Identity\Infrastructure\Persistence\Model\UserModel;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Exceptions\RoleDoesNotExist;

/**
 * Синхронизирует доменное назначение роли с таблицами Spatie Permission.
 *
 * Filament авторизуется через Spatie (web guard). Доменные роли — source of truth,
 * Spatie — проекция. Слушатель подписан в Identity\Provider::boot().
 *
 * Если Spatie-роль не заведена (SpatieRoleSeeder не выполнен), ошибка логируется
 * и не прерывает доменный flow.
 */
final class SyncSpatieRoleOnUserRoleAssigned
{
    public function handle(UserRoleAssigned $event): void
    {
        $user = UserModel::query()->find($event->userId()->toString());

        if ($user === null) {
            return;
        }

        try {
            $user->assignRole($event->roleName()->value);
        } catch (RoleDoesNotExist $e) {
            Log::warning('Spatie role missing during sync on UserRoleAssigned', [
                'user_id' => $event->userId()->toString(),
                'role' => $event->roleName()->value,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
