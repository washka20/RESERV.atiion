<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Persistence\Model;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

final class UserModel extends Authenticatable implements FilamentUser
{
    use HasFactory;
    use HasRoles;
    use Notifiable;

    protected $table = 'users';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guard_name = 'web';

    protected $fillable = [
        'id',
        'email',
        'first_name',
        'last_name',
        'middle_name',
        'password',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function domainRoles(): BelongsToMany
    {
        return $this->belongsToMany(RoleModel::class, 'role_user', 'user_id', 'role_id');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasAnyRole(['admin', 'manager']);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->last_name} {$this->first_name} {$this->middle_name}");
    }
}
