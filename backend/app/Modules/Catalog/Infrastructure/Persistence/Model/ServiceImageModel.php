<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Persistence\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eloquent модель для таблицы `service_images`. Infrastructure-слой Catalog.
 */
final class ServiceImageModel extends Model
{
    protected $table = 'service_images';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(ServiceModel::class, 'service_id');
    }
}
