<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Payment\Application;

use App\Shared\Application\Transaction\TransactionManagerInterface;

/**
 * In-memory TransactionManager — вызывает callable напрямую без БД.
 * Используется в unit-тестах Payment handlers.
 */
final class PassthroughTransactionManager implements TransactionManagerInterface
{
    public function transactional(callable $work): mixed
    {
        return $work();
    }
}
