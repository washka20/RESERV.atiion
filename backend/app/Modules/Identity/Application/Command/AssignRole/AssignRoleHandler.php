<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Command\AssignRole;

use App\Modules\Identity\Domain\Repository\RoleRepositoryInterface;
use App\Modules\Identity\Domain\Repository\UserRepositoryInterface;
use App\Modules\Identity\Domain\ValueObject\UserId;
use App\Shared\Application\Event\DomainEventDispatcherInterface;
use RuntimeException;

final readonly class AssignRoleHandler
{
    public function __construct(
        private UserRepositoryInterface $users,
        private RoleRepositoryInterface $roles,
        private DomainEventDispatcherInterface $dispatcher,
    ) {}

    public function handle(AssignRoleCommand $command): void
    {
        $user = $this->users->findById(new UserId($command->userId));
        if ($user === null) {
            throw new RuntimeException("User {$command->userId} not found");
        }

        $role = $this->roles->findByName($command->roleName);
        if ($role === null) {
            throw new RuntimeException("Role {$command->roleName->value} not found");
        }

        $user->assignRole($role);
        $this->users->save($user);
        $this->dispatcher->dispatchAll($user->pullDomainEvents());
    }
}
