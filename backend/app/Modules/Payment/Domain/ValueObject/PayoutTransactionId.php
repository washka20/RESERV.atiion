<?php

declare(strict_types=1);

namespace App\Modules\Payment\Domain\ValueObject;

use App\Shared\Domain\AggregateId;

/**
 * Идентификатор выплаты провайдеру (UUID). Aggregate id для PayoutTransaction.
 */
final class PayoutTransactionId extends AggregateId {}
