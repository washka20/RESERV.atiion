<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Outbox\Console;

use App\Shared\Infrastructure\Outbox\OutboxWorker;
use Illuminate\Console\Command;

/**
 * Artisan-команда для запуска Outbox worker'а.
 *
 *   --once — один проход runOnce() и выход (для cron / тестов).
 *   без флага — бесконечный цикл с graceful SIGTERM/SIGINT.
 */
final class OutboxWorkCommand extends Command
{
    /** @var string */
    protected $signature = 'app:outbox:work {--once : Process one batch and exit}';

    /** @var string */
    protected $description = 'Process outbox messages (at-least-once delivery)';

    public function handle(OutboxWorker $worker): int
    {
        if ($this->option('once')) {
            $count = $worker->runOnce();
            $this->info("processed {$count}");

            return self::SUCCESS;
        }

        if (function_exists('pcntl_async_signals')) {
            pcntl_async_signals(true);
            pcntl_signal(SIGTERM, static function () use ($worker): void {
                $worker->shouldStop = true;
            });
            pcntl_signal(SIGINT, static function () use ($worker): void {
                $worker->shouldStop = true;
            });
        }

        $this->info('Outbox worker started. Ctrl+C to stop gracefully.');
        $worker->run();
        $this->info('Worker stopped.');

        return self::SUCCESS;
    }
}
