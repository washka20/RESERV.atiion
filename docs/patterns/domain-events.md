# Domain Events

Domain events — факты из прошлого модуля, публикуемые для согласования состояния между модулями.

## Flow (pull-based)

```
Handler                     AggregateRoot              Dispatcher                  Listeners
  |                              |                         |                           |
  |-- $ar->doSomething()  ------>|                         |                           |
  |                              |-- recordEvent(e1) ----->|                           |
  |                              |<-- ack                  |                           |
  |<-- $ar saved via repo ------|                         |                           |
  |                              |                         |                           |
  |-- $ar->pullDomainEvents() -->|                         |                           |
  |<-- [e1] --------------------|                         |                           |
  |-- dispatcher->dispatchAll() ---------------------------->|                         |
  |                                                        |-- events->dispatch(name, e)|
  |                                                        |----------------------------|
  |                                                        |-- events->dispatch(object) |
  |                                                        |----------------------------|
```

Ключевые правила:

- Сущность **записывает** событие через `recordEvent(DomainEvent)` (protected)
- Handler / Application-service **выгребает** накопленные события через `pullDomainEvents()` после успешного сохранения
- `pullDomainEvents()` **очищает** буфер (семантика "отдал — забыл") — чтобы исключить двойную публикацию

## Когда publish reliable = true (через Outbox)

- `BookingConfirmed`, `BookingCancelled`
- `PaymentReceived`, `PaymentRefunded`, `PaymentFailed`

Всё остальное — `publish($e, reliable: false)` → Laravel Events/Queues.

## Подписка

В `Module\Provider::boot()`:

```php
use App\Shared\Infrastructure\Event\ModuleEventSubscriber;

ModuleEventSubscriber::register($this->app['events'], [
    BookingConfirmed::class => [
        SendBookingConfirmationEmail::class,
        NotifyLandingWebhook::class,
    ],
]);
```

## Интерфейс события

```php
interface DomainEvent
{
    public function aggregateId(): string;
    public function occurredAt(): DateTimeImmutable;
    public function eventName(): string;       // "booking.confirmed"
    public function payload(): array;          // сериализуемый снимок
}
```

`payload()` — **только примитивы и массивы**, чтобы событие сериализовалось в outbox_messages JSONB.

## Booking events in practice

Booking BC — пример self-contained модуля, который **публикует события, но не слушает**. Сейчас (Plan 7) консьюмеров нет, но интеграция с Payment / Notification уже спроектирована вокруг следующих событий:

| Событие | Публикуется | Гипотетический консьюмер | Use case |
|---------|-------------|--------------------------|----------|
| `BookingCreated` | `Booking::createTimeSlotBooking()` / `createQuantityBooking()` | Payment BC | Инициирует платёж: создаёт Payment в статусе PENDING и запрашивает redirect-URL у платёжного шлюза |
| `BookingConfirmed` | `Booking::confirm()` | Notification BC | Email "Ваше бронирование подтверждено" + push для мобилки |
| `BookingCancelled` | `Booking::cancel(CancellationPolicy)` | Payment BC (refund), Notification BC (letter) | Возврат средств если payment = CAPTURED, письмо клиенту |
| `BookingCompleted` | `Booking::complete()` | Catalog BC (rating), Analytics | Enable review flow, инкремент rating-агрегата услуги |
| `TimeSlotGenerated` | `saveMany` batch в `EloquentTimeSlotRepository` | Analytics | Tracking расписания |
| `TimeSlotReserved` | `TimeSlot::reserve(BookingId)` | — (внутренний факт) | Outbox для аудита |
| `TimeSlotReleased` | `TimeSlot::release()` / `CancelBookingHandler` | — | Outbox для аудита |

Реальный живой пример listener-моста между BC — `SyncSpatieRoleOnUserRoleAssigned` в Identity (слушает свой же `UserRoleAssigned` и синхронизирует Spatie-tables для Filament). Booking → Payment / Notification листенеры появятся в Plan 8+.

Reliability:
- Критичные для side-effects (`BookingConfirmed`, `BookingCancelled`) — публикуются через Outbox (см. [ADR-005](../adr/005-outbox-pattern.md)).
- Остальные — обычные Laravel Events (`reliable: false`).

## См. также
- [ADR 005 — Outbox](../adr/005-outbox-pattern.md)
- [`app/Shared/Domain/AggregateRoot.php`](../../backend/app/Shared/Domain/AggregateRoot.php)
- [Booking module docs](../modules/booking.md) — sequence diagrams с точками публикации событий
