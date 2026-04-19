<?php

declare(strict_types=1);

namespace App\Modules\Payment\Domain\Entity;

use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Identity\Domain\ValueObject\OrganizationId;
use App\Modules\Payment\Domain\Event\PayoutSettingsUpdated;
use App\Modules\Payment\Domain\ValueObject\BankAccount;
use App\Modules\Payment\Domain\ValueObject\PayoutSchedule;
use App\Modules\Payment\Domain\ValueObject\PayoutSettingsId;
use App\Shared\Domain\AggregateRoot;
use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Настройки выплат организации. Aggregate root внутри Payment BC.
 *
 * Один PayoutSettings на organization (UNIQUE по organization_id).
 * Любое изменение (bank account / schedule / minimum) публикует PayoutSettingsUpdated
 * с только OrganizationId в payload — чувствительные данные через event не идут.
 */
final class PayoutSettings extends AggregateRoot
{
    private function __construct(
        private readonly PayoutSettingsId $id,
        private readonly OrganizationId $organizationId,
        private BankAccount $bankAccount,
        private PayoutSchedule $schedule,
        private Money $minimumPayout,
    ) {}

    /**
     * Создаёт настройки выплат и записывает PayoutSettingsUpdated.
     */
    public static function create(
        PayoutSettingsId $id,
        OrganizationId $organizationId,
        BankAccount $bankAccount,
        PayoutSchedule $schedule,
        Money $minimumPayout,
    ): self {
        self::assertMinimumNonNegative($minimumPayout);

        $settings = new self($id, $organizationId, $bankAccount, $schedule, $minimumPayout);
        $settings->recordEvent(new PayoutSettingsUpdated($organizationId, new DateTimeImmutable));

        return $settings;
    }

    /**
     * Восстанавливает PayoutSettings из persistence без записи событий.
     */
    public static function reconstitute(
        PayoutSettingsId $id,
        OrganizationId $organizationId,
        BankAccount $bankAccount,
        PayoutSchedule $schedule,
        Money $minimumPayout,
    ): self {
        return new self($id, $organizationId, $bankAccount, $schedule, $minimumPayout);
    }

    /**
     * Меняет банковские реквизиты и публикует PayoutSettingsUpdated.
     * Идемпотентно: одинаковый счёт не пишет event.
     */
    public function updateBankAccount(BankAccount $account): void
    {
        if ($this->bankAccount->equals($account)) {
            return;
        }

        $this->bankAccount = $account;
        $this->recordEvent(new PayoutSettingsUpdated($this->organizationId, new DateTimeImmutable));
    }

    /**
     * Меняет расписание выплат.
     */
    public function changeSchedule(PayoutSchedule $schedule): void
    {
        if ($this->schedule === $schedule) {
            return;
        }

        $this->schedule = $schedule;
        $this->recordEvent(new PayoutSettingsUpdated($this->organizationId, new DateTimeImmutable));
    }

    /**
     * Меняет минимальную сумму выплаты.
     *
     * @throws InvalidArgumentException если сумма отрицательная
     */
    public function changeMinimumPayout(Money $minimumPayout): void
    {
        self::assertMinimumNonNegative($minimumPayout);

        if ($this->minimumPayout->equals($minimumPayout)) {
            return;
        }

        $this->minimumPayout = $minimumPayout;
        $this->recordEvent(new PayoutSettingsUpdated($this->organizationId, new DateTimeImmutable));
    }

    public function id(): PayoutSettingsId
    {
        return $this->id;
    }

    public function organizationId(): OrganizationId
    {
        return $this->organizationId;
    }

    public function bankAccount(): BankAccount
    {
        return $this->bankAccount;
    }

    public function schedule(): PayoutSchedule
    {
        return $this->schedule;
    }

    public function minimumPayout(): Money
    {
        return $this->minimumPayout;
    }

    private static function assertMinimumNonNegative(Money $amount): void
    {
        if ($amount->amount() < 0) {
            throw new InvalidArgumentException('minimum payout must be non-negative');
        }
    }
}
