<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\Query\ListPayoutsByOrganization;

use App\Modules\Payment\Application\DTO\PayoutTransactionDTO;
use Illuminate\Database\ConnectionInterface;

/**
 * Handler ListPayoutsByOrganizationQuery.
 *
 * Read-side CQRS: обходит Eloquent, читает payout_transactions напрямую через DB connection,
 * возвращает DTO[] + meta-информацию пагинации. Сортировка: новые выплаты сверху.
 *
 * @phpstan-type PayoutMeta array{page: int, per_page: int, total: int, last_page: int}
 */
final readonly class ListPayoutsByOrganizationHandler
{
    public function __construct(private ConnectionInterface $db) {}

    /**
     * @return array{items: list<PayoutTransactionDTO>, meta: PayoutMeta}
     */
    public function handle(ListPayoutsByOrganizationQuery $query): array
    {
        $page = max(1, $query->page);
        $perPage = max(1, $query->perPage);

        $total = (int) $this->db->table('payout_transactions')
            ->where('organization_id', $query->organizationId)
            ->count();

        $rows = $this->db->table('payout_transactions')
            ->where('organization_id', $query->organizationId)
            ->orderBy('created_at', 'desc')
            ->forPage($page, $perPage)
            ->get();

        $items = [];
        foreach ($rows as $row) {
            $items[] = PayoutTransactionDTO::fromRow($row);
        }

        return [
            'items' => $items,
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int) max(1, (int) ceil($total / $perPage)),
            ],
        ];
    }
}
