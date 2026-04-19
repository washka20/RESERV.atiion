<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Persistence\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eloquent-модель membership'а. Соединяет User и Organization с role и invitedBy.
 * Unique (user_id, organization_id) — enforced на уровне БД.
 */
final class MembershipModel extends Model
{
    use HasUuids;

    protected $table = 'memberships';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = [];

    protected $casts = [
        'accepted_at' => 'immutable_datetime',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(OrganizationModel::class, 'organization_id');
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'invited_by');
    }
}
