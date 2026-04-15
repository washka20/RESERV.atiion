<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Command\DeleteUser;

use App\Modules\Identity\Domain\Repository\UserRepositoryInterface;
use App\Modules\Identity\Domain\ValueObject\UserId;
use RuntimeException;

final readonly class DeleteUserHandler
{
    public function __construct(
        private UserRepositoryInterface $users,
    ) {}

    public function handle(DeleteUserCommand $command): void
    {
        $user = $this->users->findById(new UserId($command->userId));
        if ($user === null) {
            throw new RuntimeException("User {$command->userId} not found");
        }

        $this->users->delete($user);
    }
}
