<?php

declare(strict_types=1);

namespace App\Modules\Payment\Domain\Gateway;

/**
 * Результат createCharge от платёжного шлюза.
 *
 * При success=true providerRef содержит идентификатор транзакции у провайдера.
 * При success=false errorMessage содержит причину отказа.
 */
final readonly class GatewayChargeResult
{
    public function __construct(
        public bool $success,
        public ?string $providerRef,
        public ?string $errorMessage,
    ) {}
}
