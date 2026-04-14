<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Persistence\Mapper;

use App\Modules\Identity\Domain\Entity\Role;
use App\Modules\Identity\Domain\Entity\User;
use App\Modules\Identity\Domain\ValueObject\Email;
use App\Modules\Identity\Domain\ValueObject\FullName;
use App\Modules\Identity\Domain\ValueObject\HashedPassword;
use App\Modules\Identity\Domain\ValueObject\UserId;
use App\Modules\Identity\Infrastructure\Persistence\Model\RoleModel;
use App\Modules\Identity\Infrastructure\Persistence\Model\UserModel;

final class UserMapper
{
    public static function toDomain(UserModel $model): User
    {
        /** @var list<Role> $roles */
        $roles = [];
        foreach ($model->roles as $roleModel) {
            /** @var RoleModel $roleModel */
            $roles[] = RoleMapper::toDomain($roleModel);
        }

        return User::restore(
            new UserId($model->id),
            new Email($model->email),
            new HashedPassword($model->password),
            new FullName($model->first_name, $model->last_name, $model->middle_name),
            $roles,
            $model->email_verified_at?->toDateTimeImmutable(),
            $model->created_at->toDateTimeImmutable(),
        );
    }

    /**
     * Find-or-new by id; set fields. Caller сам вызывает save().
     */
    public static function toEloquent(User $user): UserModel
    {
        $model = UserModel::find($user->id()->toString()) ?? new UserModel;

        $model->id = $user->id()->toString();
        $model->email = $user->email()->value();
        $model->first_name = $user->fullName()->firstName();
        $model->last_name = $user->fullName()->lastName();
        $model->middle_name = $user->fullName()->middleName();
        $model->password = $user->passwordHash()->value();
        $model->email_verified_at = $user->emailVerifiedAt();

        return $model;
    }
}
