# Booking Module

> Bounded Context: бронирование, временные слоты, проверка доступности (core BC платформы)

## Purpose

Booking — ядро платформы. Отвечает за создание, подтверждение, отмену и завершение бронирований. Инкапсулирует бизнес-правила ("можно ли забронировать?", "можно ли отменить?"), защищает инвариант "один слот = одно бронирование" и корректное количество для QUANTITY-услуг под конкурентной нагрузкой.

BC поддерживает **dual-type** модель бронирования в одном aggregate `Booking`: выбор стратегии управляется `ServiceType` из Catalog BC. Подробности: [docs/modules/booking.md](../../../../docs/modules/booking.md).

Модуль публикует domain events при создании / подтверждении / отмене / завершении бронирований — консьюмеры (Payment, Notification) слушают их для своих use-case. Публикует также события жизненного цикла `TimeSlot` (generated / reserved / released).

## Dual-type concept: TIME_SLOT vs QUANTITY

| Аспект | TIME_SLOT | QUANTITY |
|--------|-----------|----------|
| Примеры услуг | консультации, массаж, теннисный корт | гостиничные номера, прокат велосипедов, билеты на сеанс |
| Инвариант | один слот = одно бронирование | sum(active.quantity) ≤ service.totalQuantity на любой overlapping диапазон |
| Привязка | `slot_id` + `TimeRange` | `DateRange(check_in, check_out)` + `Quantity` |
| Race-protection | atomic `UPDATE ... WHERE is_booked=false` | `SELECT ... FOR UPDATE` + sum в PHP |
| Требования к услуге | `Service.type === TIME_SLOT`, `duration !== null` | `Service.type === QUANTITY`, `totalQuantity > 0` |
| Цена бронирования | `Service.price` | `Service.price * quantity * nights()` |

Выбор ветки идёт по `ServiceType` из Catalog BC в `CreateBookingHandler`. Несоответствие входных данных типу услуги → `InvalidBookingTypeException` (HTTP 409).

## Aggregates / Entities

- **Booking** (aggregate root, [`Domain/Entity/Booking.php`](Domain/Entity/Booking.php)) — id, userId, serviceId, type, status, (slotId|dateRange+quantity), totalPrice, notes, createdAt, updatedAt.
  - Фабрики: `createTimeSlotBooking`, `createQuantityBooking`, `reconstitute`.
  - Actions: `confirm()`, `cancel(CancellationPolicy)`, `complete()`. Каждое действие валидирует state transition и emit event.
  - Computed: `isActive()`.
- **TimeSlot** (aggregate root, [`Domain/Entity/TimeSlot.php`](Domain/Entity/TimeSlot.php)) — id, serviceId, range, isBooked, bookingId.
  - Фабрики: `create`, `reconstitute`.
  - Actions: `reserve(BookingId)` (проверка isBooked → SlotAlreadyBookedException), `release()` (идемпотентно).

## Value Objects

- `BookingId`, `SlotId` — extends `Shared/Domain/AggregateId` (UUID v7)
- `BookingStatus` — enum (`pending` | `confirmed` | `cancelled` | `completed`) с методами `isActive()` / `isFinal()`
- `BookingType` — enum (`time_slot` | `quantity`)
- `TimeRange` — startAt + endAt (immutable, валидация endAt > startAt)
- `DateRange` — checkIn + checkOut (DATE), метод `nights()`, factory `fromStrings`
- `Quantity` — positive int (валидация ≥ 1)

## Specifications & Policies

Атомарные (композируемые через AND / OR / NOT из Shared kernel):

- `SlotIsAvailable` — слот существует и `isBooked=false`
- `QuantityIsAvailable` — `sum(active.quantity) + requested ≤ totalQuantity`
- `WithinBookingWindow` — забронировать нельзя в прошлом и дальше чем за N дней вперёд
- `WithinCancellationWindow` — отмена возможна не позднее чем за M часов до даты
- `UserNotExceedsLimit` — `active bookings < limit` (default 20)
- `BookingNotAlreadyCompleted` — нельзя отменить/изменить COMPLETED

Композиты:

- `BookingPolicy` — `WithinBookingWindow AND UserNotExceedsLimit`. Используется в `CreateBookingHandler` через `isSatisfiedByWithContext`.
- `CancellationPolicy` — `WithinCancellationWindow AND BookingNotAlreadyCompleted`. Передаётся в `Booking::cancel()`.

## Strategy Pattern — Availability

`Domain/Service/AvailabilityChecker` — dispatcher, выбирает стратегию по `Service.type`:

- `TimeSlotAvailabilityStrategy` — список свободных слотов на дату
- `QuantityAvailabilityStrategy` — total / booked / available на диапазон дат

Результат: `AvailabilityResult` — полиморфный, сериализуется в `AvailabilityDTO` (абстракт, конкретизация: `TimeSlotAvailabilityDTO` / `QuantityAvailabilityDTO`).

Добавление третьего типа (например SEATS для кинотеатра) — новая стратегия + новое значение `ServiceType` в Catalog, без правок существующих стратегий. См. [docs/modules/booking.md](../../../../docs/modules/booking.md#extending).

## Concurrency

Booking BC требует PostgreSQL — используются row-locking и атомарные conditional updates:

- `EloquentTimeSlotRepository::markAsBooked(SlotId, BookingId): bool` — `UPDATE ... WHERE id = ? AND is_booked = false`. `affected = 1` → лидер резервации; `affected = 0` → кто-то опередил, caller бросает `SlotUnavailableException`.
- `EloquentBookingRepository::sumActiveQuantityOverlapping(ServiceId, DateRange, lockForUpdate: true)` — `SELECT id, quantity ... FOR UPDATE`, агрегация в PHP. PG запрещает `SELECT SUM(...) FOR UPDATE`, поэтому берём строки с row-lock и суммируем в приложении.
- Вся операция `CreateBookingHandler::handle` обёрнута в `TransactionManagerInterface::transactional` — lock держится до commit'а транзакции.

Полный разбор сценариев: [ADR-011 Booking concurrency strategy](../../../../docs/adr/011-booking-concurrency-strategy.md).

## Domain Events

**Публикуемые** (все в [`Domain/Event/`](Domain/Event/)):

- `BookingCreated(bookingId, userId, serviceId, BookingType, occurredAt)` — после фабрики Booking
- `BookingConfirmed(bookingId, occurredAt)` — после `confirm()`
- `BookingCancelled(bookingId, BookingType, ?slotId, occurredAt)` — после `cancel()` (slotId нужен консьюмерам для release слота)
- `BookingCompleted(bookingId, occurredAt)` — после `complete()`
- `TimeSlotGenerated(slotId, serviceId, TimeRange, occurredAt)` — после batch-генерации
- `TimeSlotReserved(slotId, bookingId, occurredAt)` — после `reserve()`
- `TimeSlotReleased(slotId, occurredAt)` — после `release()`

**Слушаемые:** нет (Booking — self-contained BC).

**Гипотетические будущие потребители (Plan 8+):**

- Payment BC — слушает `BookingCreated` → инициирует платёж
- Notification BC — слушает `BookingConfirmed` / `BookingCancelled` → шлёт email / push
- Catalog BC — опционально слушает `BookingCompleted` для рейтинг-агрегации

Пример реального listener-моста между BC: `Identity\...\SyncSpatieRoleOnUserRoleAssigned`.

## Commands (CQRS write через CommandBus)

| Command | Handler emits | Бросает |
|---------|---------------|---------|
| `CreateBookingCommand` | `BookingCreated`, `TimeSlotReserved` (если TIME_SLOT) | `ServiceNotFoundException`, `SlotUnavailableException`, `InsufficientQuantityException`, `InvalidBookingTypeException` |
| `ConfirmBookingCommand` | `BookingConfirmed` | `BookingNotFoundException`, `InvalidBookingStateTransitionException` |
| `CancelBookingCommand` | `BookingCancelled`, `TimeSlotReleased` (если slotId) | `BookingNotFoundException`, `CancellationNotAllowedException` |
| `CompleteBookingCommand` | `BookingCompleted` | `BookingNotFoundException`, `InvalidBookingStateTransitionException` |
| `ReleaseTimeSlotCommand` | `TimeSlotReleased` | — (идемпотентно) |
| `GenerateTimeSlotsCommand` | `TimeSlotGenerated` (batch) | `ServiceNotFoundException`, `InvalidBookingTypeException` (service.type !== TIME_SLOT) |

## Queries (CQRS read через QueryBus)

Все query handlers — `DB::table()` + DTO, без Eloquent (см. [ADR-007](../../../../docs/adr/007-read-side-without-eloquent.md)).

| Query | Параметры | DTO |
|-------|-----------|-----|
| `CheckAvailabilityQuery` | serviceId, ServiceType, ?date, ?DateRange, ?requested | `TimeSlotAvailabilityDTO` или `QuantityAvailabilityDTO` |
| `GetBookingQuery` | bookingId, actorUserId, isAdmin | `BookingDTO` (ownership check) |
| `ListUserBookingsQuery` | userId, ?status, page, perPage | `BookingListResult` |
| `ListAllBookingsQuery` | ?status, page, perPage | `BookingListResult` (admin only, без ownership filter) |
| `ListTimeSlotsQuery` | ?serviceId, ?dateFrom, ?dateTo, ?isBooked, page, perPage | `TimeSlotListResult` |

## API endpoints (prefix `/api/v1`, JWT)

| Метод | URL | Auth | Результат |
|-------|-----|------|-----------|
| GET | `/services/{service}/availability?type=...&date=... \| ?check_in=...&check_out=...&requested=N` | Bearer | 200 + `AvailabilityDTO` (polymorphic) |
| GET | `/bookings?status=...&page=...&per_page=...` | Bearer | 200 + `BookingListItem[]` + pagination |
| GET | `/bookings/{id}` | Bearer | 200 + `BookingDTO` (только владелец/admin) |
| POST | `/bookings` | Bearer | 201 + `BookingDTO` |
| PATCH | `/bookings/{id}/cancel` | Bearer | 200 + `BookingDTO` (only owner) |

### Envelope (успех, POST /bookings)

```json
{
  "success": true,
  "data": {
    "id": "018f-...",
    "user_id": "018e-...",
    "service_id": "018d-...",
    "type": "time_slot",
    "status": "pending",
    "slot_id": "018c-...",
    "time_range": { "start_at": "2026-05-01T10:00:00+00:00", "end_at": "2026-05-01T11:00:00+00:00" },
    "total_price": { "amount": 150000, "currency": "RUB" },
    "notes": null,
    "created_at": "2026-04-18T12:00:00+00:00"
  },
  "error": null,
  "meta": null
}
```

### Envelope (ошибка)

```json
{
  "success": false,
  "data": null,
  "error": {
    "code": "BOOKING_SLOT_UNAVAILABLE",
    "message": "Slot 018c-... is not available",
    "details": { "slot_id": "018c-..." }
  },
  "meta": null
}
```

### Error codes

| Код | HTTP | Когда |
|-----|------|-------|
| `BOOKING_SLOT_UNAVAILABLE` | 409 | Попытка забронировать отсутствующий / уже занятый слот |
| `BOOKING_SLOT_ALREADY_BOOKED` | 409 | Race: конкурент успел первым — markAsBooked вернул false |
| `BOOKING_INSUFFICIENT_QUANTITY` | 409 | `booked + requested > totalQuantity` на диапазон |
| `BOOKING_NOT_FOUND` | 404 | `GET /bookings/{id}` / `PATCH /cancel` с несуществующим id |
| `BOOKING_CANCELLATION_NOT_ALLOWED` | 409 | `CancellationPolicy` не удовлетворена (окно / уже completed) |
| `BOOKING_INVALID_TYPE` | 409 | Несовпадение типа команды и `Service.type` |
| `BOOKING_INVALID_STATE_TRANSITION` | 409 | Confirm не-PENDING, complete не-CONFIRMED |
| `SERVICE_NOT_FOUND` | 404 | Services.findById вернул null |
| `VALIDATION_ERROR` | 422 | FormRequest валидация |

## Filament Resources (`/admin`)

- [`Interface/Filament/Resource/BookingResource`](Interface/Filament/Resource/BookingResource.php) — read-only таблица со статусом-badge, фильтрами (status, type, service, user), Actions:
  - `ConfirmBookingAction` → `ConfirmBookingCommand`
  - `CancelBookingAction` → `CancelBookingCommand`
  - `CompleteBookingAction` → `CompleteBookingCommand`
- [`Interface/Filament/Resource/TimeSlotResource`](Interface/Filament/Resource/TimeSlotResource.php) — read-only список слотов с фильтрами и индикатором isBooked
- [`Interface/Filament/Page/GenerateTimeSlotsPage`](Interface/Filament/Page/GenerateTimeSlotsPage.php) — custom page с batch-формой: service, dateFrom, dateTo, slotDuration, workingHours → диспатчит `GenerateTimeSlotsCommand` → batch INSERT через `saveMany`

Все writes идут через CommandBus — Resource не вызывает Eloquent::save напрямую.

## Dependencies

**На другие модули (через Shared interfaces):**

- **Catalog**: `ServiceRepositoryInterface`, `Service` entity, `ServiceType` enum, `Money` VO — Booking читает услугу для dispatching стратегии и цены.
- **Identity**: `UserId` VO — cross-BC reference by ID, без импорта `User` entity.

**Shared kernel:**

- `Shared/Domain/AggregateRoot`, `AggregateId`, `DomainEvent`, `Specification`, `Exception/DomainException`
- `Shared/Application/Bus/CommandBusInterface`, `QueryBusInterface`
- `Shared/Application/Event/DomainEventDispatcherInterface`
- `Shared/Application/Transaction/TransactionManagerInterface` — абстракция `DB::transaction`, обеспечивает testability Application handler без Laravel app

**От других модулей ожидается (события):** см. раздел Domain Events выше.

## Frontend (Vue SPA)

### Routes

| Path | View | data-test-id контейнера |
|------|------|-------------------------|
| `/booking/new?service_id=...` | `BookingView` | `booking-page` |
| `/booking/confirm/:id` | `BookingConfirmView` | `booking-confirm-page` |
| `/dashboard` | `DashboardView` | `dashboard-page` |

### Components

- `BookingForm` — orchestrator, рендерит TimeSlotPicker или QuantityDatePicker по `service.type`
- `TimeSlotPicker` — список свободных слотов на дату
- `QuantityDatePicker` — check_in / check_out datepicker + quantity spinner
- `BookingSummary` — price preview, notes, submit
- `BookingCard`, `BookingsList`, `BookingFilters` — Dashboard UI

Store: `useBookingStore` (Pinia setup). API: `booking.api.ts`, `availability.api.ts`.

## Запуск тестов модуля

```bash
docker compose -f docker-compose.yml -f docker-compose.dev.yml exec -T php \
    ./vendor/bin/pest \
        tests/Unit/Modules/Booking \
        tests/Feature/Booking \
        tests/Feature/Api/Booking \
        tests/Feature/Filament/Booking
```

## Test coverage

- Unit: [`tests/Unit/Modules/Booking/`](../../../tests/Unit/Modules/Booking/) — Domain VO + Entities + Specifications + Availability strategies + Application handlers (Mockery)
- API: [`tests/Feature/Api/Booking/`](../../../tests/Feature/Api/Booking/) — 20 тестов (HTTP flow, envelope, error codes, ownership, pagination)
- Concurrency: [`tests/Feature/Booking/ConcurrentBookingTest.php`](../../../tests/Feature/Booking/ConcurrentBookingTest.php) — стресс-проверка защиты от race (требует PG, fork-based)
- Filament: [`tests/Feature/Filament/Booking/`](../../../tests/Feature/Filament/Booking/) — CRUD / actions / generate page через Livewire
- E2E: [`frontend/e2e/spec/booking/`](../../../../frontend/e2e/spec/booking/) — 10 тестов (dashboard, time-slot flow, quantity flow)

### Known limitation — E2E мокают backend

E2E-тесты используют `page.route` для перехвата HTTP-запросов к `/api/v1/**`. Причина — все booking endpoints защищены JWT (`JwtAuthMiddleware`), а SPA пока не имеет login flow и axios auth interceptor (JWT integration на фронте в scope Plan 8/9).

Что покрыто E2E: UI-логика (клики, state transitions, роутинг), формат ожидаемого payload.
Что не покрыто E2E: реальная интеграция backend↔frontend через HTTP.

Backend API contract покрыт **20 Feature-тестами** против реального PG + **concurrency**-тестом. Когда JWT flow появится на фронте — E2E переписываются на real backend или HAR-based моки.

## Ссылки

- [docs/modules/booking.md](../../../../docs/modules/booking.md) — deep dive, diagrams, примеры
- [docs/adr/011-booking-concurrency-strategy.md](../../../../docs/adr/011-booking-concurrency-strategy.md) — ADR по concurrency
- [docs/patterns/domain-events.md](../../../../docs/patterns/domain-events.md) — pattern domain events
- [docs/patterns/specification-pattern.md](../../../../docs/patterns/specification-pattern.md) — pattern specifications
