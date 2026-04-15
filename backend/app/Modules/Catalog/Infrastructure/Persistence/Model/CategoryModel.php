<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Persistence\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Eloquent модель для таблицы `categories`. Infrastructure-слой Catalog.
 *
 * Используется исключительно в мапперах и Filament UI. Доменная логика — в Category aggregate.
 */
final class CategoryModel extends Model
{
    protected $table = 'categories';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function subcategories(): HasMany
    {
        return $this->hasMany(SubcategoryModel::class, 'category_id')->orderBy('sort_order');
    }

    public function services(): HasMany
    {
        return $this->hasMany(ServiceModel::class, 'category_id');
    }
}
