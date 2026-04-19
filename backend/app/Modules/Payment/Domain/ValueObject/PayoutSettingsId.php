<?php

declare(strict_types=1);

namespace App\Modules\Payment\Domain\ValueObject;

use App\Shared\Domain\AggregateId;

/**
 * Идентификатор payout settings (UUID). Aggregate id для PayoutSettings.
 */
final class PayoutSettingsId extends AggregateId {}
