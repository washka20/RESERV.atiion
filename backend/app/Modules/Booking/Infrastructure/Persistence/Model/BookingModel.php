<?php

declare(strict_types=1);

namespace App\Modules\Booking\Infrastructure\Persistence\Model;

use App\Modules\Catalog\Infrastructure\Persistence\Model\ServiceModel;
use App\Modules\Identity\Infrastructure\Persistence\Model\UserModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    /**
     * Владелец бронирования. Relation используется в Filament UI для отображения.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }

    /**
     * Услуга, к которой относится бронирование. Relation для Filament UI.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(ServiceModel::class, 'service_id');
    }
}
