<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Persistence\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Eloquent модель для таблицы `services`. Infrastructure-слой Catalog.
 *
 * Не является доменной сущностью — преобразуется в Service aggregate через ServiceMapper.
 */
final class ServiceModel extends Model
{
    protected $table = 'services';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'price_amount' => 'integer',
        'duration_minutes' => 'integer',
        'total_quantity' => 'integer',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(CategoryModel::class, 'category_id');
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(SubcategoryModel::class, 'subcategory_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ServiceImageModel::class, 'service_id')->orderBy('sort_order');
    }
}
