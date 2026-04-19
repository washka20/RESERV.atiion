<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\Command\MarkPaymentFailed;

use App\Modules\Payment\Domain\ValueObject\PaymentId;

/**
 * Команда «отметить платёж как провалившийся».
 *
 * reason — человекочитаемое описание причины для логов / уведомлений пользователя.
 */
final readonly class MarkPaymentFailedCommand
{
    public function __construct(
        public PaymentId $id,
        public string $reason,
    ) {}
}
