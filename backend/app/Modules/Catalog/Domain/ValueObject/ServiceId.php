<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\ValueObject;

use App\Shared\Domain\AggregateId;

/**
 * Идентификатор услуги (UUID). Aggregate id для Service.
 */
final class ServiceId extends AggregateId {}
