<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Command\VerifyEmail;

use App\Modules\Identity\Domain\Repository\UserRepositoryInterface;
use App\Modules\Identity\Domain\ValueObject\UserId;
use App\Shared\Application\Event\DomainEventDispatcherInterface;
use RuntimeException;

final readonly class VerifyEmailHandler
{
    public function __construct(
        private UserRepositoryInterface $users,
        private DomainEventDispatcherInterface $dispatcher,
    ) {}

    public function handle(VerifyEmailCommand $command): void
    {
        $user = $this->users->findById(new UserId($command->userId));
        if ($user === null) {
            throw new RuntimeException("User {$command->userId} not found");
        }

        $user->verifyEmail();
        $this->users->save($user);
        $this->dispatcher->dispatchAll($user->pullDomainEvents());
    }
}
