<?php

declare(strict_types=1);

namespace App\Modules\Payment\Infrastructure\Persistence\Model;

use App\Modules\Booking\Infrastructure\Persistence\Model\BookingModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eloquent model для таблицы payments. Только persistence — доменная логика в Payment entity.
 */
final class PaymentModel extends Model
{
    use HasUuids;

    protected $table = 'payments';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'amount_cents' => 'integer',
        'platform_fee_cents' => 'integer',
        'net_amount_cents' => 'integer',
        'marketplace_fee_percent' => 'integer',
        'paid_at' => 'immutable_datetime',
    ];

    /**
     * Связь с бронированием (1:1). Используется в Filament UI при чтении.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(BookingModel::class, 'booking_id');
    }
}
