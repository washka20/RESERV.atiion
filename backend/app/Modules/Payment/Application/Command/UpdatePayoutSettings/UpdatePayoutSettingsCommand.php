<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\Command\UpdatePayoutSettings;

/**
 * Команда «обновить payout settings организации».
 *
 * Проверка принадлежности user'а organization выполняется handler'ом через
 * {@see \App\Shared\Application\Identity\MembershipLookupInterface::isOwner()}.
 */
final readonly class UpdatePayoutSettingsCommand
{
    public function __construct(
        public string $userId,
        public string $organizationId,
        public string $bankName,
        public string $accountNumber,
        public string $accountHolder,
        public string $bic,
        public string $schedule,
        public int $minimumPayoutCents,
    ) {}
}
