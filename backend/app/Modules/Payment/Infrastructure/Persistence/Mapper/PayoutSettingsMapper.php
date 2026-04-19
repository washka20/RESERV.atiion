<?php

declare(strict_types=1);

namespace App\Modules\Payment\Infrastructure\Persistence\Mapper;

use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Identity\Domain\ValueObject\OrganizationId;
use App\Modules\Payment\Domain\Entity\PayoutSettings;
use App\Modules\Payment\Domain\ValueObject\BankAccount;
use App\Modules\Payment\Domain\ValueObject\PayoutSchedule;
use App\Modules\Payment\Domain\ValueObject\PayoutSettingsId;
use App\Modules\Payment\Infrastructure\Persistence\Model\PayoutSettingsModel;
use Illuminate\Support\Facades\Crypt;

/**
 * Bidirectional mapper PayoutSettings <-> PayoutSettingsModel.
 *
 * toDomain расшифровывает account_number через {@see Crypt::decryptString()}.
 * toModel шифрует plaintext номер счёта и пересчитывает last-4 маску для UI.
 */
final class PayoutSettingsMapper
{
    public static function toDomain(PayoutSettingsModel $model): PayoutSettings
    {
        $accountNumber = Crypt::decryptString((string) $model->account_number_encrypted);

        $bankAccount = new BankAccount(
            bankName: (string) $model->bank_name,
            accountNumber: $accountNumber,
            accountHolder: (string) $model->account_holder,
            bic: (string) $model->bic,
        );

        return PayoutSettings::reconstitute(
            id: new PayoutSettingsId((string) $model->id),
            organizationId: new OrganizationId((string) $model->organization_id),
            bankAccount: $bankAccount,
            schedule: PayoutSchedule::from((string) $model->payout_schedule),
            minimumPayout: Money::fromCents((int) $model->minimum_payout_cents),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function toArray(PayoutSettings $settings): array
    {
        $account = $settings->bankAccount();

        return [
            'id' => $settings->id()->toString(),
            'organization_id' => $settings->organizationId()->toString(),
            'bank_name' => $account->bankName,
            'account_number_encrypted' => Crypt::encryptString($account->accountNumber),
            'account_number_masked' => $account->masked(),
            'account_holder' => $account->accountHolder,
            'bic' => $account->bic,
            'payout_schedule' => $settings->schedule()->value,
            'minimum_payout_cents' => $settings->minimumPayout()->amount(),
        ];
    }
}
