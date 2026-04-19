<?php

declare(strict_types=1);

namespace App\Modules\Payment\Domain\Gateway;

/**
 * Результат refund от платёжного шлюза.
 *
 * При success=false errorMessage содержит причину отказа (напр. already refunded).
 */
final readonly class GatewayRefundResult
{
    public function __construct(
        public bool $success,
        public ?string $errorMessage,
    ) {}
}
