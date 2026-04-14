<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Persistence\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class RoleModel extends Model
{
    protected $table = 'roles';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'name'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(UserModel::class, 'role_user', 'role_id', 'user_id');
    }
}
