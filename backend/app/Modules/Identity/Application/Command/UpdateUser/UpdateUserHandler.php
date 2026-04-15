<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Command\UpdateUser;

use App\Modules\Identity\Domain\Exception\DuplicateEmailException;
use App\Modules\Identity\Domain\Repository\UserRepositoryInterface;
use App\Modules\Identity\Domain\ValueObject\Email;
use App\Modules\Identity\Domain\ValueObject\FullName;
use App\Modules\Identity\Domain\ValueObject\UserId;
use App\Shared\Application\Event\DomainEventDispatcherInterface;
use RuntimeException;

final readonly class UpdateUserHandler
{
    public function __construct(
        private UserRepositoryInterface $users,
        private DomainEventDispatcherInterface $dispatcher,
    ) {}

    public function handle(UpdateUserCommand $command): void
    {
        $user = $this->users->findById(new UserId($command->userId));
        if ($user === null) {
            throw new RuntimeException("User {$command->userId} not found");
        }

        if ($command->email !== null) {
            $newEmail = new Email($command->email);
            if (! $newEmail->equals($user->email())) {
                if ($this->users->existsByEmail($newEmail)) {
                    throw new DuplicateEmailException("Email {$command->email} already taken");
                }
                $user->changeEmail($newEmail);
            }
        }

        if ($command->firstName !== null || $command->lastName !== null || $command->middleName !== null) {
            $firstName = $command->firstName ?? $user->fullName()->firstName();
            $lastName = $command->lastName ?? $user->fullName()->lastName();
            $middleName = $command->middleName ?? $user->fullName()->middleName();
            $user->changeName(new FullName($firstName, $lastName, $middleName));
        }

        $this->users->save($user);
        $this->dispatcher->dispatchAll($user->pullDomainEvents());
    }
}
