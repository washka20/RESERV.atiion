<?php

declare(strict_types=1);

namespace App\Modules\Payment\Infrastructure\Persistence\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent-модель для organization_payout_settings. Номер счёта хранится зашифрованным
 * (Laravel Crypt) в колонке account_number_encrypted + last-4 в account_number_masked.
 */
final class PayoutSettingsModel extends Model
{
    use HasUuids;

    protected $table = 'organization_payout_settings';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'minimum_payout_cents' => 'integer',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];
}
