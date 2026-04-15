<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\ValueObject;

use App\Shared\Domain\AggregateId;

/**
 * Идентификатор подкатегории (UUID). Aggregate id для Subcategory.
 */
final class SubcategoryId extends AggregateId {}
