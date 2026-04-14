<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Command\RegisterUser;

use App\Modules\Identity\Domain\Entity\User;
use App\Modules\Identity\Domain\Exception\DuplicateEmailException;
use App\Modules\Identity\Domain\Repository\RoleRepositoryInterface;
use App\Modules\Identity\Domain\Repository\UserRepositoryInterface;
use App\Modules\Identity\Domain\Service\PasswordHasherInterface;
use App\Modules\Identity\Domain\ValueObject\Email;
use App\Modules\Identity\Domain\ValueObject\FullName;
use App\Modules\Identity\Domain\ValueObject\HashedPassword;
use App\Modules\Identity\Domain\ValueObject\RoleName;
use App\Modules\Identity\Domain\ValueObject\UserId;
use App\Shared\Application\Event\DomainEventDispatcherInterface;

final readonly class RegisterUserHandler
{
    public function __construct(
        private UserRepositoryInterface $users,
        private RoleRepositoryInterface $roles,
        private PasswordHasherInterface $hasher,
        private DomainEventDispatcherInterface $dispatcher,
    ) {}

    public function handle(RegisterUserCommand $command): UserId
    {
        $email = new Email($command->email);
        if ($this->users->existsByEmail($email)) {
            throw new DuplicateEmailException("Email {$command->email} already taken");
        }

        $user = User::register(
            UserId::generate(),
            $email,
            HashedPassword::fromPlaintext($command->plaintextPassword, $this->hasher),
            new FullName($command->firstName, $command->lastName, $command->middleName),
        );

        $defaultRole = $this->roles->findByName(RoleName::User);
        if ($defaultRole !== null) {
            $user->assignRole($defaultRole);
        }

        $this->users->save($user);
        $this->dispatcher->dispatchAll($user->pullDomainEvents());

        return $user->id();
    }
}
