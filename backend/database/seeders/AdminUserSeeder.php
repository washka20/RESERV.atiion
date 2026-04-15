<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Identity\Application\Command\AssignRole\AssignRoleCommand;
use App\Modules\Identity\Application\Command\RegisterUser\RegisterUserCommand;
use App\Modules\Identity\Domain\ValueObject\RoleName;
use App\Shared\Application\Bus\CommandBusInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Создаёт администратора через CommandBus.
 *
 * Domain events публикуются handler-ами, Spatie sync listeners автоматически
 * синхронизируют роли с таблицами Spatie Permission.
 */
final class AdminUserSeeder extends Seeder
{
    public function __construct(private readonly CommandBusInterface $commandBus) {}

    public function run(): void
    {
        $email = 'admin@example.com';

        if (DB::table('users')->where('email', $email)->exists()) {
            return;
        }

        $userId = $this->commandBus->dispatch(new RegisterUserCommand(
            email: $email,
            plaintextPassword: 'password123',
            firstName: 'Admin',
            lastName: 'User',
        ));

        DB::table('users')->where('email', $email)->update(['email_verified_at' => now()]);

        $this->commandBus->dispatch(new AssignRoleCommand(
            userId: $userId->toString(),
            roleName: RoleName::Admin,
        ));
    }
}
