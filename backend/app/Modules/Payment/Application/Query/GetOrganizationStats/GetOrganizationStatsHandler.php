<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\Query\GetOrganizationStats;

use App\Modules\Payment\Application\DTO\OrganizationStatsDTO;
use DateTimeImmutable;
use Illuminate\Database\ConnectionInterface;

/**
 * Handler GetOrganizationStatsQuery.
 *
 * Read-side агрегация за последние 30 дней:
 * - revenue/platformFee/netPayout — сумма по payout_transactions (успешные и в процессе).
 * - bookings30d — count bookings за период.
 * - conversionRate — placeholder 0.0 (считается по воронке visits, данных ещё нет).
 * - topServices — top-5 услуг по количеству bookings за период.
 */
final readonly class GetOrganizationStatsHandler
{
    public function __construct(private ConnectionInterface $db) {}

    public function handle(GetOrganizationStatsQuery $query): OrganizationStatsDTO
    {
        $since = (new DateTimeImmutable('-30 days'))->format('Y-m-d H:i:s');

        $payouts = $this->db->table('payout_transactions')
            ->where('organization_id', $query->organizationId)
            ->where('created_at', '>=', $since)
            ->selectRaw('COALESCE(SUM(gross_amount_cents), 0) AS revenue, COALESCE(SUM(platform_fee_cents), 0) AS fee, COALESCE(SUM(net_amount_cents), 0) AS net')
            ->first();

        $bookings30d = (int) $this->db->table('bookings')
            ->join('services', 'bookings.service_id', '=', 'services.id')
            ->where('services.organization_id', $query->organizationId)
            ->where('bookings.created_at', '>=', $since)
            ->count();

        $topServicesRaw = $this->db->table('bookings')
            ->join('services', 'bookings.service_id', '=', 'services.id')
            ->where('services.organization_id', $query->organizationId)
            ->where('bookings.created_at', '>=', $since)
            ->selectRaw('services.id AS id, services.name AS title, COUNT(bookings.id) AS bookings_count')
            ->groupBy('services.id', 'services.name')
            ->orderByDesc('bookings_count')
            ->limit(5)
            ->get();

        $topServices = [];
        foreach ($topServicesRaw as $row) {
            $topServices[] = [
                'id' => (string) $row->id,
                'title' => (string) $row->title,
                'bookings' => (int) $row->bookings_count,
            ];
        }

        return new OrganizationStatsDTO(
            revenue30dCents: (int) ($payouts->revenue ?? 0),
            platformFee30dCents: (int) ($payouts->fee ?? 0),
            netPayout30dCents: (int) ($payouts->net ?? 0),
            bookings30d: $bookings30d,
            conversionRate: 0.0,
            topServices: $topServices,
        );
    }
}
