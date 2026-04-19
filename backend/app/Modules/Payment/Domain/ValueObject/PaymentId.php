<?php

declare(strict_types=1);

namespace App\Modules\Payment\Domain\ValueObject;

use App\Shared\Domain\AggregateId;

/**
 * Идентификатор платежа (UUID). Aggregate id для Payment.
 */
final class PaymentId extends AggregateId {}
