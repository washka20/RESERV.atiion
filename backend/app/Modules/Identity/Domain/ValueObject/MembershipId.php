<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\ValueObject;

use App\Shared\Domain\AggregateId;

/**
 * Идентификатор членства (UUID). Aggregate id для Membership.
 */
final class MembershipId extends AggregateId {}
