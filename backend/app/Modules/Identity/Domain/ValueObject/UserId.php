<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\ValueObject;

use App\Shared\Domain\AggregateId;

/** Strongly-typed identifier for the User aggregate. */
final class UserId extends AggregateId {}
