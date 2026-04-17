<?php

declare(strict_types=1);

namespace App\Modules\Booking\Infrastructure\Persistence\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent model для таблицы bookings. Только для persistence — доменная логика в Booking entity.
 */
final class BookingModel extends Model
{
    use HasUuids;

    protected $table = 'bookings';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'start_at' => 'immutable_datetime',
        'end_at' => 'immutable_datetime',
        'check_in' => 'immutable_date',
        'check_out' => 'immutable_date',
        'quantity' => 'integer',
        'total_price_amount' => 'decimal:2',
    ];
}
