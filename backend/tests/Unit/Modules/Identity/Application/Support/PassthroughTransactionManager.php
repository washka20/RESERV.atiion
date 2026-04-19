<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Identity\Application\Support;

use App\Shared\Application\Transaction\TransactionManagerInterface;

/**
 * In-memory TransactionManager: просто выполняет callable без транзакционной
 * обвязки. Используется в Unit-тестах command handlers.
 */
final class PassthroughTransactionManager implements TransactionManagerInterface
{
    public function transactional(callable $work): mixed
    {
        return $work();
    }
}
