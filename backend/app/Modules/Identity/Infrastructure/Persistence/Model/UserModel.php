<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Persistence\Model;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

final class UserModel extends Authenticatable
{
    protected $table = 'users';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'email',
        'first_name',
        'last_name',
        'middle_name',
        'password',
        'email_verified_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(RoleModel::class, 'role_user', 'user_id', 'role_id');
    }
}
