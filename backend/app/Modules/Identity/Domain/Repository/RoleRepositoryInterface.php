<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Repository;

use App\Modules\Identity\Domain\Entity\Role;
use App\Modules\Identity\Domain\ValueObject\RoleId;
use App\Modules\Identity\Domain\ValueObject\RoleName;

interface RoleRepositoryInterface
{
    public function findByName(RoleName $name): ?Role;

    public function findById(RoleId $id): ?Role;
}
