<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\DTO;

use App\Modules\Payment\Domain\Entity\PayoutSettings;

/**
 * DTO настроек выплат организации для API слоя.
 *
 * Поле accountNumberMasked — всегда маска (последние 4 знака); plaintext номер счёта
 * не покидает Application слой через этот DTO (API не отдаёт его клиенту).
 */
final readonly class PayoutSettingsDTO
{
    public function __construct(
        public string $organizationId,
        public string $bankName,
        public string $accountNumberMasked,
        public string $accountHolder,
        public string $bic,
        public string $payoutSchedule,
        public int $minimumPayoutCents,
    ) {}

    public static function fromEntity(PayoutSettings $settings): self
    {
        $account = $settings->bankAccount();

        return new self(
            organizationId: $settings->organizationId()->toString(),
            bankName: $account->bankName,
            accountNumberMasked: $account->masked(),
            accountHolder: $account->accountHolder,
            bic: $account->bic,
            payoutSchedule: $settings->schedule()->value,
            minimumPayoutCents: $settings->minimumPayout()->amount(),
        );
    }
}
