<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\Command\UpdatePayoutSettings;

use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Identity\Domain\ValueObject\OrganizationId;
use App\Modules\Payment\Domain\Entity\PayoutSettings;
use App\Modules\Payment\Domain\Repository\PayoutSettingsRepositoryInterface;
use App\Modules\Payment\Domain\ValueObject\BankAccount;
use App\Modules\Payment\Domain\ValueObject\PayoutSchedule;
use App\Modules\Payment\Domain\ValueObject\PayoutSettingsId;
use App\Shared\Application\Identity\MembershipLookupInterface;
use App\Shared\Application\Outbox\OutboxPublisherInterface;
use App\Shared\Application\Transaction\TransactionManagerInterface;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Handler UpdatePayoutSettingsCommand.
 *
 * 1. Проверяет что вызывающий user — OWNER organization'а (иначе AuthorizationException).
 * 2. Если настройки уже есть — обновляет (bank account / schedule / minimum).
 *    Если нет — создаёт новый агрегат.
 * 3. В транзакции: save + publish PayoutSettingsUpdated (reliable=false — подписчиков нет в MVP).
 */
final readonly class UpdatePayoutSettingsHandler
{
    public function __construct(
        private PayoutSettingsRepositoryInterface $repo,
        private MembershipLookupInterface $memberships,
        private OutboxPublisherInterface $publisher,
        private TransactionManagerInterface $tx,
    ) {}

    /**
     * @throws AuthorizationException если user не OWNER организации
     */
    public function handle(UpdatePayoutSettingsCommand $cmd): void
    {
        if (! $this->memberships->isOwner($cmd->userId, $cmd->organizationId)) {
            throw new AuthorizationException('only organization owner can manage payout settings');
        }

        $organizationId = new OrganizationId($cmd->organizationId);
        $bankAccount = new BankAccount(
            bankName: $cmd->bankName,
            accountNumber: $cmd->accountNumber,
            accountHolder: $cmd->accountHolder,
            bic: $cmd->bic,
        );
        $schedule = PayoutSchedule::from($cmd->schedule);
        $minimum = Money::fromCents($cmd->minimumPayoutCents);

        $this->tx->transactional(function () use ($organizationId, $bankAccount, $schedule, $minimum): void {
            $settings = $this->repo->findByOrganizationId($organizationId);

            if ($settings === null) {
                $settings = PayoutSettings::create(
                    PayoutSettingsId::generate(),
                    $organizationId,
                    $bankAccount,
                    $schedule,
                    $minimum,
                );
            } else {
                $settings->updateBankAccount($bankAccount);
                $settings->changeSchedule($schedule);
                $settings->changeMinimumPayout($minimum);
            }

            $this->repo->save($settings);

            foreach ($settings->pullDomainEvents() as $event) {
                $this->publisher->publish($event, reliable: false);
            }
        });
    }
}
