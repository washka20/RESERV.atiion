<?php

declare(strict_types=1);

namespace App\Modules\Identity\Interface\Filament\Listener;

use App\Modules\Identity\Domain\Event\UserRoleRevoked;
use App\Modules\Identity\Infrastructure\Persistence\Model\UserModel;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Exceptions\RoleDoesNotExist;

/**
 * Синхронизирует доменный отзыв роли с таблицами Spatie Permission.
 *
 * Зеркало SyncSpatieRoleOnUserRoleAssigned. Слушатель подписан в Identity\Provider::boot().
 */
final class SyncSpatieRoleOnUserRoleRevoked
{
    public function handle(UserRoleRevoked $event): void
    {
        $user = UserModel::query()->find($event->userId()->toString());

        if ($user === null) {
            return;
        }

        try {
            $user->removeRole($event->roleName()->value);
        } catch (RoleDoesNotExist $e) {
            Log::warning('Spatie role missing during sync on UserRoleRevoked', [
                'user_id' => $event->userId()->toString(),
                'role' => $event->roleName()->value,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
