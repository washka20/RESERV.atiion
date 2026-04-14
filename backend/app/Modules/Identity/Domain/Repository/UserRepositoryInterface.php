<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Repository;

use App\Modules\Identity\Domain\Entity\User;
use App\Modules\Identity\Domain\ValueObject\Email;
use App\Modules\Identity\Domain\ValueObject\UserId;

interface UserRepositoryInterface
{
    public function save(User $user): void;

    public function findById(UserId $id): ?User;

    public function findByEmail(Email $email): ?User;

    public function existsByEmail(Email $email): bool;
}
