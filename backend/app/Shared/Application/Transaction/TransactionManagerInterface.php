<?php

declare(strict_types=1);

namespace App\Shared\Application\Transaction;

/**
 * Контракт менеджера транзакций для Application-слоя.
 *
 * Application handlers инжектируют эту абстракцию вместо фасада DB —
 * остаются testable без Laravel application instance.
 */
interface TransactionManagerInterface
{
    /**
     * Выполняет callable в транзакции. Все изменения commit при успехе,
     * rollback при исключении. Возвращает результат callable.
     *
     * @template T
     *
     * @param  callable(): T  $work
     * @return T
     */
    public function transactional(callable $work): mixed;
}
