<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Persistence\Repository;

use App\Modules\Identity\Domain\Entity\Role;
use App\Modules\Identity\Domain\Repository\RoleRepositoryInterface;
use App\Modules\Identity\Domain\ValueObject\RoleId;
use App\Modules\Identity\Domain\ValueObject\RoleName;
use App\Modules\Identity\Infrastructure\Persistence\Mapper\RoleMapper;
use App\Modules\Identity\Infrastructure\Persistence\Model\RoleModel;

final class EloquentRoleRepository implements RoleRepositoryInterface
{
    public function findByName(RoleName $name): ?Role
    {
        $model = RoleModel::where('name', $name->value)->first();

        return $model !== null ? RoleMapper::toDomain($model) : null;
    }

    public function findById(RoleId $id): ?Role
    {
        $model = RoleModel::find($id->toString());

        return $model !== null ? RoleMapper::toDomain($model) : null;
    }
}
