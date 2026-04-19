<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Outbox;

use DateTimeImmutable;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Обрабатывает pending outbox_messages батчами и публикует через Laravel dispatcher.
 *
 * At-least-once delivery: падение dispatch() приводит к retry с exponential backoff.
 * Batch берётся в транзакции с lockForUpdate() чтобы несколько worker'ов
 * не дублировали работу (race при горизонтальном масштабировании worker'ов).
 *
 * Статусы:
 *  - pending — ждёт обработки (next_attempt_at IS NULL OR <= now()).
 *  - published — успешно доставлено.
 *  - failed — исчерпали retry_count >= max_retries.
 */
final class OutboxWorker
{
    /** @var int максимум ретраев до перехода в failed */
    private readonly int $maxRetries;

    /** @var int сколько сообщений брать за один проход */
    private readonly int $batchSize;

    /** Флаг graceful shutdown, выставляется из SIGTERM/SIGINT handler'а. */
    public bool $shouldStop = false;

    public function __construct(
        private readonly DomainEventSerializer $serializer,
        private readonly Dispatcher $events,
        private readonly LoggerInterface $logger,
        Config $config,
    ) {
        $this->maxRetries = (int) $config->get('payments.outbox.max_retries', 10);
        $this->batchSize = (int) $config->get('payments.outbox.worker_batch_size', 50);
    }

    /**
     * Один проход: забрать батч pending-сообщений и обработать. Возвращает кол-во обработанных строк.
     */
    public function runOnce(): int
    {
        $rows = $this->claimBatch();

        if ($rows === []) {
            return 0;
        }

        foreach ($rows as $row) {
            $this->processRow($row);
        }

        return count($rows);
    }

    /**
     * Долгоживущий цикл обработки. Останавливается по выставлению $shouldStop.
     */
    public function run(): void
    {
        while (! $this->shouldStop) {
            if (function_exists('pcntl_signal_dispatch')) {
                pcntl_signal_dispatch();
            }

            $processed = $this->runOnce();

            if ($this->shouldStop) {
                break;
            }

            if ($processed === 0) {
                sleep(1);
            }
        }
    }

    /**
     * Берёт pending-сообщения готовые к обработке; в одной транзакции с lockForUpdate,
     * чтобы параллельные worker'ы не взяли одни и те же row'ы.
     *
     * @return list<OutboxMessageModel>
     */
    private function claimBatch(): array
    {
        return DB::transaction(function (): array {
            $now = new DateTimeImmutable;

            $rows = OutboxMessageModel::query()
                ->where('status', 'pending')
                ->where(function ($q) use ($now): void {
                    $q->whereNull('next_attempt_at')
                        ->orWhere('next_attempt_at', '<=', $now);
                })
                ->orderBy('created_at')
                ->limit($this->batchSize)
                ->lockForUpdate()
                ->get()
                ->all();

            return $rows;
        });
    }

    private function processRow(OutboxMessageModel $row): void
    {
        try {
            $event = $this->serializer->fromPayload($row->payload);
            $this->events->dispatch($event->eventName(), [$event]);
            $this->events->dispatch($event);

            $row->status = 'published';
            $row->published_at = new DateTimeImmutable;
            $row->last_error = null;
            $row->save();
        } catch (Throwable $e) {
            $this->handleFailure($row, $e);
        }
    }

    private function handleFailure(OutboxMessageModel $row, Throwable $e): void
    {
        $row->retry_count = $row->retry_count + 1;
        $row->last_error = $e::class.': '.$e->getMessage();

        if ($row->retry_count >= $this->maxRetries) {
            $row->status = 'failed';
            $row->failed_at = new DateTimeImmutable;
            $row->next_attempt_at = null;
        } else {
            $delay = min(3600, 2 ** $row->retry_count);
            $row->next_attempt_at = (new DateTimeImmutable)->modify("+{$delay} seconds");
        }

        $row->save();

        $this->logger->warning('outbox message dispatch failed', [
            'id' => $row->id,
            'event_type' => $row->event_type,
            'retry_count' => $row->retry_count,
            'max_retries' => $this->maxRetries,
            'status' => $row->status,
            'error' => $row->last_error,
        ]);
    }
}
