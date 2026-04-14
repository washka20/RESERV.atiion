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

## См. также
- [ADR 005 — Outbox](../adr/005-outbox-pattern.md)
- [`app/Shared/Domain/AggregateRoot.php`](../../backend/app/Shared/Domain/AggregateRoot.php)
