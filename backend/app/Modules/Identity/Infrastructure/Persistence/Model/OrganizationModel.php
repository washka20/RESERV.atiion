<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Persistence\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Eloquent-модель organization'а — persistence container, НЕ domain entity.
 * Все read/write через Organization aggregate и mapper'ы.
 */
final class OrganizationModel extends Model
{
    use HasUuids;

    protected $table = 'organizations';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = [];

    protected $casts = [
        'name' => 'array',
        'description' => 'array',
        'verified' => 'boolean',
        'rating' => 'float',
        'reviews_count' => 'integer',
        'archived_at' => 'immutable_datetime',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];

    public function memberships(): HasMany
    {
        return $this->hasMany(MembershipModel::class, 'organization_id');
    }
}
