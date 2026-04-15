<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Persistence\Repository;

use App\Modules\Identity\Domain\Entity\Role;
use App\Modules\Identity\Domain\Entity\User;
use App\Modules\Identity\Domain\Repository\UserRepositoryInterface;
use App\Modules\Identity\Domain\ValueObject\Email;
use App\Modules\Identity\Domain\ValueObject\UserId;
use App\Modules\Identity\Infrastructure\Persistence\Mapper\UserMapper;
use App\Modules\Identity\Infrastructure\Persistence\Model\UserModel;
use Illuminate\Support\Facades\DB;

final class EloquentUserRepository implements UserRepositoryInterface
{
    public function save(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $model = UserMapper::toEloquent($user);
            $model->save();

            $roleIds = array_map(
                static fn (Role $r): string => $r->id()->toString(),
                $user->roles(),
            );
            $model->domainRoles()->sync($roleIds);
        });
    }

    public function findById(UserId $id): ?User
    {
        $model = UserModel::with('domainRoles')->find($id->toString());

        return $model !== null ? UserMapper::toDomain($model) : null;
    }

    public function findByEmail(Email $email): ?User
    {
        $model = UserModel::with('domainRoles')->where('email', $email->value())->first();

        return $model !== null ? UserMapper::toDomain($model) : null;
    }

    public function existsByEmail(Email $email): bool
    {
        return UserModel::where('email', $email->value())->exists();
    }

    public function delete(User $user): void
    {
        UserModel::where('id', $user->id()->toString())->delete();
    }
}
