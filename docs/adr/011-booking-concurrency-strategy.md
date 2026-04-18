# ADR 011: Booking concurrency strategy

**Status:** Accepted
**Date:** 2026-04-18

## Context

Booking BC отвечает за бронирования двух разных семантик:

1. **TIME_SLOT** — бронирование привязано к `TimeSlot` (конкретный слот времени). Инвариант: **один слот = одно бронирование**.
2. **QUANTITY** — бронирование N единиц на диапазон дат. Инвариант: `sum(active.quantity)` на любой overlapping-диапазон ≤ `Service.totalQuantity`.

Оба типа должны оставаться корректными под конкурентной нагрузкой: два пользователя одновременно жмут "Забронировать" и должны получить предсказуемый результат — один из них получает бронирование, другой — явную ошибку `409 Conflict`.

Prod работает на PostgreSQL. Тесты тоже — после [Plan 7 Task 17](../../backend/tests/Feature/Booking/ConcurrentBookingTest.php) полностью убрали SQLite :memory: из test-suite (см. `docker-compose.test.yml` + phpunit DB_* из env). Это позволяет опираться на PG-специфичные фичи (row locks, atomic conditional update).

Разные семантики требуют разных решений:

- TIME_SLOT — есть unique **row** (один слот), можно атомарно "захватить" его conditional UPDATE'ом.
- QUANTITY — нужно суммировать несколько строк под единой точкой консистентности (sum must not exceed totalQuantity), обычный conditional UPDATE не выручает.

## Decision

### TIME_SLOT — atomic conditional UPDATE

В `EloquentTimeSlotRepository::markAsBooked`:

```sql
UPDATE time_slots
   SET is_booked = true, booking_id = :booking_id
 WHERE id = :slot_id
   AND is_booked = false
```

Возвращает `affected_rows`. Handler интерпретирует:

- `1` → успешная резервация, мы лидер гонки.
- `0` → слот был `is_booked=true` (или не существует) — `SlotUnavailableException` (HTTP 409, `BOOKING_SLOT_UNAVAILABLE`).

Conditional UPDATE — атомарная операция на уровне row-lock внутри PostgreSQL. Не нужен явный `SELECT FOR UPDATE` + UPDATE. Один round-trip, никаких советующих блокировок.

### QUANTITY — SELECT FOR UPDATE + aggregation в PHP

В `EloquentBookingRepository::sumActiveQuantityOverlapping(ServiceId, DateRange, lockForUpdate: true)`:

```sql
SELECT id, quantity
  FROM bookings
 WHERE service_id = :sid
   AND type = 'quantity'
   AND status IN ('pending', 'confirmed')
   AND check_in  <  :check_out
   AND check_out >  :check_in
   FOR UPDATE
```

PHP считает `sum(quantity)` на полученных строках. Row-level locks держатся до конца транзакции. Конкурентная транзакция ждёт на тех же строках, пересчитывает sum после commit'а первой и получает актуальный результат (`InsufficientQuantityException` если перевалили лимит).

**Почему не `SELECT SUM(quantity) ... FOR UPDATE`?** PostgreSQL этого не допускает:

```
ERROR: FOR UPDATE is not allowed with aggregate functions
```

(Семантика: `FOR UPDATE` лочит строки результата, агрегация возвращает одну логическую строку не-исходного происхождения — неоднозначно какие строки блокировать.) Поэтому берём raw rows, суммируем в приложении. Overhead незаметен — overlapping-bookings на услугу редко > 20 строк, round-trip один.

### Транзакционная граница

`CreateBookingHandler::handle` обёрнут в `TransactionManagerInterface::transactional(fn)`:

```php
public function handle(CreateBookingCommand $cmd): BookingDTO
{
    $service = $this->serviceRepo->findById(new ServiceId($cmd->serviceId));
    // ...
    return $this->tx->transactional(function () use ($cmd, $service): BookingDTO {
        // - count user bookings
        // - findById slot / sumOverlap with lock
        // - build Booking via factory
        // - check BookingPolicy
        // - save Booking (INSERT)
        // - markAsBooked (TIME_SLOT) / locks-already-held (QUANTITY)
        // - dispatch events
        // - return DTO
    });
}
```

Любой сбой внутри → rollback, locks отпускаются. Конкурирующий клиент разблокируется и пересчитывает актуальное состояние.

`TransactionManagerInterface` (в `Shared/Application/Transaction/`) — абстракция над `DB::transaction`. Infra реализует как `LaravelTransactionManager`. Application-слой не импортирует Laravel → handler testable без Laravel app (моки `tx->transactional(fn): fn()` — сразу выполняет колбэк).

## Consequences

### Плюсы

- **Простая семантика** — никаких advisory locks, queue-based serializer, optimistic retry. Конкурентная транзакция либо проходит, либо получает явную `409 Conflict`.
- **Масштабирует** — PG row-locks гранулярны. Одна услуга = небольшой набор locks; бронирования разных услуг идут параллельно без взаимного блокирования.
- **Testability** — `TransactionManagerInterface` изолирует Laravel. Unit-тесты handlers не поднимают framework. `ConcurrentBookingTest` (fork-based) проверяет реальную защиту против реального PG.
- **0 внешних зависимостей** — не используем Redis-locks / Zookeeper / queue-serializer.

### Минусы / trade-offs

- **Жёсткая привязка к PostgreSQL.** SQLite не поддерживает `FOR UPDATE`. Это зафиксировано: миграции без SQLite-guard'ов, test DB тоже PG (Plan 7 Task 17). Вернуться на SQLite нельзя без отказа от решения.
- **SELECT FOR UPDATE + SUM в PHP** — микро round-trip для подсчёта (строки уже приехали). Можно было сделать сложнее (отдельная таблица "quantity_counters" с unique lock), но выигрыш неочевиден для текущего объёма.
- **Cancellation / release** полагается на тот же row-lock механизм косвенно — отменённые бронирования выпадают из sum через `status IN ('pending', 'confirmed')`.

### Testability

- `CreateBookingHandler` unit-тесты — моки репозиториев + `tx` → прогоняют error paths без PG.
- `ConcurrentBookingTest` — реальный PG, fork()-based стресс на N процессов. Эмулирует продакшен сценарий гонки. Ассёрты:
  - TIME_SLOT: ровно **одно** успешное создание из N параллельных попыток на один слот.
  - QUANTITY: `sum(успешных.quantity) ≤ totalQuantity`.

## Alternatives considered

### Advisory locks на service_id

`pg_advisory_xact_lock(service_id.hash)` перед CreateBooking. Сериализует все бронирования одной услуги.

**Отвергнуто** — для TIME_SLOT это overkill: бронирование слота A не должно блокировать параллельное бронирование слота B той же услуги. Advisory lock упрощает QUANTITY, но даёт худший throughput для TIME_SLOT.

### Queue-based serializer

Все CreateBooking идут в очередь, worker обрабатывает sequentially per service.

**Отвергнуто** — для текущего объёма overkill. Добавляет infrastructure (queue worker), увеличивает latency ("Ваше бронирование обрабатывается…"), усложняет error reporting (клиент уже получил 202 — как вернуть `409`?).

### Optimistic locking с retry

Добавить `version` column в `bookings` / `time_slots`, `UPDATE ... WHERE version = :old`, retry N раз при конфликте.

**Отвергнуто** — сложнее кодово, UX-проблема при retry (что если все N попыток упали? сливаем 500?). Пессимистический lock проще и предсказуемее для бронирований, где действительный конфликт редок.

### Unique constraint на `(service_id, check_in, check_out)` для QUANTITY

Не применимо — QUANTITY допускает несколько бронирований на overlapping-диапазон до `totalQuantity`. Unique не подходит.

## References

- [`EloquentTimeSlotRepository::markAsBooked`](../../backend/app/Modules/Booking/Infrastructure/Persistence/Repository/EloquentTimeSlotRepository.php)
- [`EloquentBookingRepository::sumActiveQuantityOverlapping`](../../backend/app/Modules/Booking/Infrastructure/Persistence/Repository/EloquentBookingRepository.php)
- [`CreateBookingHandler`](../../backend/app/Modules/Booking/Application/Command/CreateBooking/CreateBookingHandler.php)
- [`TransactionManagerInterface`](../../backend/app/Shared/Application/Transaction/TransactionManagerInterface.php)
- [`ConcurrentBookingTest`](../../backend/tests/Feature/Booking/ConcurrentBookingTest.php)
- [docs/modules/booking.md — Concurrency deep-dive](../modules/booking.md#concurrency-deep-dive)
- Plan 7 Task 17 — переход test suite на PostgreSQL
