<?php

declare(strict_types=1);

namespace App\Modules\Payment\Infrastructure\Worker;

use App\Modules\Identity\Domain\ValueObject\OrganizationId;
use App\Modules\Payment\Application\Command\MarkPayoutPaid\MarkPayoutPaidCommand;
use App\Modules\Payment\Domain\Repository\PayoutSettingsRepositoryInterface;
use App\Modules\Payment\Domain\ValueObject\PayoutSchedule;
use App\Shared\Application\Bus\CommandBusInterface;
use Illuminate\Database\ConnectionInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Worker выплат организациям.
 *
 * Для каждой organization с PENDING-выплатами:
 *  1. Находит payout settings (если нет — пропускает).
 *  2. Проверяет расписание (WEEKLY во вторник, BIWEEKLY во вторник чётной недели, MONTHLY 5 числа, ON_REQUEST никогда).
 *  3. Проверяет что сумма net >= минимальной (minimum_payout_cents).
 *  4. Диспатчит MarkPayoutPaidCommand для каждого payout в группе (последовательно, ошибка одного не ломает других).
 *
 * Флаг --force (ignoreSchedule=true) пропускает проверку расписания — для manual runs.
 */
final class PayoutWorker
{
    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly PayoutSettingsRepositoryInterface $settingsRepo,
        private readonly CommandBusInterface $bus,
        private readonly LoggerInterface $logger,
    ) {}

    public function processPending(bool $ignoreSchedule = false): int
    {
        $groups = $this->db->table('payout_transactions')
            ->where('status', 'pending')
            ->select('organization_id')
            ->selectRaw('SUM(net_amount_cents) AS total_net')
            ->selectRaw('array_agg(id::text) AS ids')
            ->groupBy('organization_id')
            ->get();

        $processed = 0;
        foreach ($groups as $group) {
            $settings = $this->settingsRepo->findByOrganizationId(
                new OrganizationId((string) $group->organization_id),
            );

            if ($settings === null) {
                $this->logger->info('payout worker: skip org without settings', [
                    'organization_id' => $group->organization_id,
                ]);

                continue;
            }

            if (! $ignoreSchedule && ! $this->isDueBySchedule($settings->schedule())) {
                continue;
            }

            if ((int) $group->total_net < $settings->minimumPayout()->amount()) {
                continue;
            }

            foreach ($this->pgArrayToList((string) $group->ids) as $payoutId) {
                try {
                    $this->bus->dispatch(new MarkPayoutPaidCommand($payoutId));
                    $processed++;
                } catch (Throwable $e) {
                    $this->logger->error('payout worker: mark paid failed', [
                        'payout_id' => $payoutId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $processed;
    }

    /**
     * Проверяет, подходит ли текущий день под расписание.
     *
     * BIWEEKLY — вторник чётной ISO-недели. MONTHLY — 5 число месяца.
     * ON_REQUEST — никогда автоматически (только через --force).
     */
    private function isDueBySchedule(PayoutSchedule $schedule): bool
    {
        return match ($schedule) {
            PayoutSchedule::WEEKLY => now()->isTuesday(),
            PayoutSchedule::BIWEEKLY => now()->isTuesday() && ((int) now()->weekOfYear % 2 === 0),
            PayoutSchedule::MONTHLY => now()->day === 5,
            PayoutSchedule::ON_REQUEST => false,
        };
    }

    /**
     * Разбирает PostgreSQL массив `{id1,id2,...}` в список строк.
     *
     * @return list<string>
     */
    private function pgArrayToList(string $raw): array
    {
        $trimmed = trim($raw, '{}');
        if ($trimmed === '') {
            return [];
        }

        return array_values(array_filter(
            array_map('trim', explode(',', $trimmed)),
            static fn (string $v): bool => $v !== '',
        ));
    }
}
