<?php

declare(strict_types=1);

namespace App\Modules\Booking\Infrastructure\Persistence\Model;

use App\Modules\Catalog\Infrastructure\Persistence\Model\ServiceModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eloquent model для таблицы time_slots. Только для persistence — доменная логика в TimeSlot entity.
 */
final class TimeSlotModel extends Model
{
    use HasUuids;

    protected $table = 'time_slots';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'start_at' => 'immutable_datetime',
        'end_at' => 'immutable_datetime',
        'is_booked' => 'boolean',
    ];

    /**
     * Услуга, к которой относится слот. Relation для Filament UI.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(ServiceModel::class, 'service_id');
    }
}
