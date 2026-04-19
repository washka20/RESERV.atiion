<?php

declare(strict_types=1);

namespace App\Modules\Payment\Interface\Console;

use App\Modules\Payment\Infrastructure\Worker\PayoutWorker;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Artisan-команда запуска PayoutWorker.
 *
 *   app:payouts:process            — запуск с проверкой расписания (для cron).
 *   app:payouts:process --force    — запуск с ignoreSchedule=true (manual).
 */
final class PayoutsProcessCommand extends Command
{
    /** @var string */
    protected $signature = 'app:payouts:process {--force : Ignore payout schedule and process all eligible groups}';

    /** @var string */
    protected $description = 'Process pending payouts grouped by organization (respects schedule and minimum)';

    public function handle(PayoutWorker $worker): int
    {
        $force = (bool) $this->option('force');
        $count = $worker->processPending(ignoreSchedule: $force);

        $this->info("processed {$count} payouts");
        Log::channel((string) config('payments.payouts.log_channel', 'payouts'))
            ->info('payout worker run', ['processed' => $count, 'force' => $force]);

        return self::SUCCESS;
    }
}
