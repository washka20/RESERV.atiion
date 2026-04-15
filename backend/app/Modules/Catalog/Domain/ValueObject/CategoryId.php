<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\ValueObject;

use App\Shared\Domain\AggregateId;

/**
 * Идентификатор категории (UUID). Aggregate id для Category.
 */
final class CategoryId extends AggregateId {}
