<?php

declare(strict_types=1);

namespace App\Modules\Booking\Domain\ValueObject;

use App\Shared\Domain\AggregateId;

/**
 * Идентификатор временного слота (UUID). Aggregate id для TimeSlot.
 */
final class SlotId extends AggregateId {}
