<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\ValueObject;

use App\Shared\Domain\AggregateId;

/**
 * Идентификатор организации (UUID). Aggregate id для Organization.
 */
final class OrganizationId extends AggregateId {}
