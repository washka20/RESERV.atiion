<?php

declare(strict_types=1);

namespace App\Modules\Booking\Infrastructure\Persistence\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

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
}
