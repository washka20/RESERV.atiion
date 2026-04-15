<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Persistence\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Eloquent модель для таблицы `subcategories`. Infrastructure-слой Catalog.
 *
 * Subcategory — child entity внутри агрегата Category.
 */
final class SubcategoryModel extends Model
{
    protected $table = 'subcategories';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(CategoryModel::class, 'category_id');
    }

    public function services(): HasMany
    {
        return $this->hasMany(ServiceModel::class, 'subcategory_id');
    }
}
