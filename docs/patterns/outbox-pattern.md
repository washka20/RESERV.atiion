# Outbox Pattern

> Гарантированная доставка критичных Domain Events через таблицу в той же транзакции, что и доменное состояние.

## Зачем

**Dual-write problem.** Когда сервис одновременно пишет в БД и публикует событие в брокер/очередь, любая из двух операций может упасть после того, как вторая успела выполниться:

- БД commit → queue down → подписчик не узнал о `PaymentReceived` → booking не confirmed.
- Queue published → БД crash → подписчик получил событие о том, чего нет.

**Нельзя** совместить два независимых хранилища в одной атомарной транзакции без распределённых транзакций (2PC — мёртвая технология для новых систем).

**Outbox** решает это, превращая публикацию в локальную БД-операцию:

1. Сохраняем доменное состояние **и** запись об событии в одной и той же транзакции PostgreSQL.
2. Отдельный фоновый worker читает outbox-таблицу и публикует в Queue.
3. Если worker падает — события останутся в таблице и будут опубликованы при следующем прогоне.

Так гарантируется **at-least-once delivery** — событие будет опубликовано хотя бы один раз.

## Где применяется в RESERV.atiion

**Reliable** (через Outbox):

- `PaymentReceived` — платёж получен → booking.confirmed + payout transaction.
- `PaymentRefunded` — возврат → compensation handlers.
- `BookingConfirmed`, `BookingCancelled`.
- `PayoutMarkedPaid` — провайдер получил выплату.

**Не через Outbox** (fire-and-forget Laravel Events):

- `PaymentInitiated`, `PaymentFailed` — информативные, потеря допустима.
- `PayoutTransactionCreated`, `PayoutSettingsUpdated` — аналитика, не критично.
- Внутренние UI-уведомления, логи.

См. `docs/adr/005-outbox-pattern.md`.

## Schema

```sql
CREATE TABLE outbox_messages (
    id BIGSERIAL PRIMARY KEY,
    event_type VARCHAR(255) NOT NULL,
    payload JSONB NOT NULL,
    aggregate_type VARCHAR(255) NULL,
    aggregate_id VARCHAR(255) NULL,
    published_at TIMESTAMPTZ NULL,
    failed_at TIMESTAMPTZ NULL,
    attempts INT NOT NULL DEFAULT 0,
    last_error TEXT NULL,
    available_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_outbox_pending
    ON outbox_messages (available_at)
    WHERE published_at IS NULL;

CREATE INDEX idx_outbox_aggregate
    ON outbox_messages (aggregate_type, aggregate_id);
```

## Writer — публикация в транзакции

`App\Shared\Application\Outbox\OutboxPublisherInterface`:

```php
public function publish(DomainEvent $event, bool $reliable = true): void;
```

Реализация записывает JSON-payload в `outbox_messages` **внутри** активной транзакции:

```php
DB::transaction(function () use ($payment, $events) {
    $this->paymentRepository->save($payment);

    foreach ($events as $event) {
        $this->outboxPublisher->publish($event, reliable: true);
    }
});
```

Если транзакция rolled back — outbox-запись тоже откатывается. Если commit успешен — событие гарантированно в таблице.

## Worker lifecycle

`app:outbox:work` (`OutboxWorker`):

```
loop:
    fetch batch of pending (available_at <= now, published_at IS NULL) LIMIT batch_size
    for each message:
        try:
            deserialize payload
            dispatch as Laravel Event / webhook / queue job
            UPDATE outbox_messages SET published_at = NOW() WHERE id = ?
        catch:
            UPDATE outbox_messages
                SET attempts = attempts + 1,
                    last_error = ?,
                    available_at = NOW() + backoff(attempts),
                    failed_at = (attempts >= max_retries ? NOW() : NULL)
                WHERE id = ?
    sleep 1s if batch was empty
    exit on SIGTERM (graceful)
```

Конфиг: `config/payments.php::outbox`:

- `worker_batch_size: 50` — сколько сообщений брать за итерацию.
- `max_retries: 10` — после 10 неудач сообщение помечается `failed_at` и требует ручного вмешательства.

## Retry / Backoff

Exponential, capped at 1 hour:

```
backoff(n) = min(2^n, 3600) seconds
```

| attempts | delay |
|----------|-------|
| 1        | 2s    |
| 2        | 4s    |
| 3        | 8s    |
| 5        | 32s   |
| 10       | 1024s |
| 11+      | 3600s (capped) |

После `max_retries=10` сообщение **не удаляется** — остаётся с `failed_at = NOW()`. Операционный alerting должен мониторить эту колонку.

## Idempotency (listeners)

**At-least-once** означает: listener может получить одно и то же событие несколько раз.

**Listener обязан быть идемпотентным.** Варианты:

- **Domain-level guard:** `Payment::markPaid()` бросает `PaymentAlreadyPaidException` если уже `paid`. `ConfirmBookingHandler` бросает если booking уже `confirmed`.
- **Event-level dedup:** хранить `processed_event_ids` в отдельной таблице per listener. Перед обработкой — `INSERT ON CONFLICT DO NOTHING`, если conflict — skip.
- **Natural idempotency:** если listener только PATCH-ит state в терминальный (paid, cancelled) — повторное применение не меняет результат.

В RESERV.atiion используется первый вариант: aggregates защищают себя сами, handlers ловят `DomainException` и логируют как `already processed`, не роняя worker.

## Operational

**Метрики для мониторинга:**

- `outbox.pending_count` = `SELECT count(*) WHERE published_at IS NULL AND available_at <= now()` — очередь на обработку. Alert if > 1000 sustained.
- `outbox.failed_count` = `SELECT count(*) WHERE failed_at IS NOT NULL` — требует ручного вмешательства. Alert if > 0.
- `outbox.lag_seconds` = `SELECT EXTRACT(EPOCH FROM (NOW() - MIN(created_at))) WHERE published_at IS NULL` — latency. Alert if > 60s.

**Worker HA:**

MVP запускает **один** worker-контейнер (supervisor restart on crash). Для масштабирования — multi-worker через `SELECT ... FOR UPDATE SKIP LOCKED`:

```sql
SELECT * FROM outbox_messages
WHERE published_at IS NULL AND available_at <= NOW()
ORDER BY id
LIMIT 50
FOR UPDATE SKIP LOCKED;
```

Каждый worker берёт свой lock, не конфликтует с другими. TODO: Plan N+1.

## Alternatives / Future

1. **Debezium + CDC** — Postgres logical replication → Kafka. Отвергнут для MVP: оверкилл, +1 инфраструктурный компонент. Миграция возможна без изменения writer — worker заменяется на Debezium connector, таблица `outbox_messages` становится source of truth для CDC.
2. **Transactional messaging (JMS, Azure SB Transactions)** — не применимо, PostgreSQL + Laravel.
3. **2PC / XA** — категорический отказ: операторная сложность, performance penalty, тренд на избегание.

## Связанные документы

- [ADR-005: Outbox Pattern](../adr/005-outbox-pattern.md)
- [ADR-009: Без Saga на старте](../adr/009-no-saga-on-start.md)
- [Patterns: Domain Events](domain-events.md)
- [Payment Module README](../../backend/app/Modules/Payment/README.md)
