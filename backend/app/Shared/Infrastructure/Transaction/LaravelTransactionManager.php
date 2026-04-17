<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Transaction;

use App\Shared\Application\Transaction\TransactionManagerInterface;
use Illuminate\Database\DatabaseManager;

/**
 * Laravel реализация TransactionManager поверх DatabaseManager.
 */
final readonly class LaravelTransactionManager implements TransactionManagerInterface
{
    public function __construct(
        private DatabaseManager $db,
    ) {}

    public function transactional(callable $work): mixed
    {
        return $this->db->transaction($work);
    }
}
