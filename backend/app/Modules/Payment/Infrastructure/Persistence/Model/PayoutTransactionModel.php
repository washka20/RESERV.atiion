<?php

declare(strict_types=1);

namespace App\Modules\Payment\Infrastructure\Persistence\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent-модель для payout_transactions.
 */
final class PayoutTransactionModel extends Model
{
    use HasUuids;

    protected $table = 'payout_transactions';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'gross_amount_cents' => 'integer',
        'platform_fee_cents' => 'integer',
        'net_amount_cents' => 'integer',
        'scheduled_at' => 'immutable_datetime',
        'paid_at' => 'immutable_datetime',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];
}
