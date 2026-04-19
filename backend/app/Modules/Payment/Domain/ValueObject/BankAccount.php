<?php

declare(strict_types=1);

namespace App\Modules\Payment\Domain\ValueObject;

use InvalidArgumentException;

/**
 * Банковские реквизиты для выплат организации.
 *
 * Валидация в конструкторе: account number >= 10 знаков, BIC — ровно 9 цифр (РФ формат).
 * Номер счёта хранится в plaintext внутри VO; шифрование — ответственность Mapper'а при персисте.
 */
final readonly class BankAccount
{
    public function __construct(
        public string $bankName,
        public string $accountNumber,
        public string $accountHolder,
        public string $bic,
    ) {
        if (strlen($accountNumber) < 10) {
            throw new InvalidArgumentException('account number too short');
        }

        if (! preg_match('/^\d{9}$/', $bic)) {
            throw new InvalidArgumentException('BIC must be 9 digits');
        }
    }

    /**
     * Возвращает маскированную версию номера счёта для отображения ("•••• 4472").
     */
    public function masked(): string
    {
        return '•••• '.substr($this->accountNumber, -4);
    }

    public function equals(self $other): bool
    {
        return $this->bankName === $other->bankName
            && $this->accountNumber === $other->accountNumber
            && $this->accountHolder === $other->accountHolder
            && $this->bic === $other->bic;
    }
}
