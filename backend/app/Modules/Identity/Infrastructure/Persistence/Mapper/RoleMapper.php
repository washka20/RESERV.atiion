<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Persistence\Mapper;

use App\Modules\Identity\Domain\Entity\Role;
use App\Modules\Identity\Domain\ValueObject\RoleId;
use App\Modules\Identity\Domain\ValueObject\RoleName;
use App\Modules\Identity\Infrastructure\Persistence\Model\RoleModel;

final class RoleMapper
{
    public static function toDomain(RoleModel $model): Role
    {
        return new Role(
            new RoleId($model->id),
            RoleName::from($model->name),
            $model->created_at?->toDateTimeImmutable(),
        );
    }
}
