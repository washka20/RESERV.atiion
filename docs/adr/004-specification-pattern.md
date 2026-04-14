# ADR 004: Specification Pattern для бизнес-правил

**Status:** Accepted
**Date:** 2026-04-14

## Context

Бизнес-правила в Booking (CancellationPolicy, BookingPolicy) и Catalog (ServiceAvailability) будут эволюционировать. Нужен способ:
- Комбинировать правила (AND, OR, NOT)
- Тестировать правила изолированно
- Получать понятные причины отказа

## Decision

**Specification Pattern.** Базовый `Specification` в `app/Shared/Domain/Specification/`. Каждая спецификация — отдельный класс в `app/Modules/*/Domain/Specification/`.

```php
$canBook = (new ServiceIsActive($service))
    ->and(new SlotIsAvailable($slot))
    ->and(new WithinBookingWindow($date));

if (!$canBook->isSatisfiedBy($context)) {
    throw new BookingNotAllowedException($canBook->failureReason());
}
```

## Consequences

**Плюсы:**
- Правила тестируются изолированно
- Композиция из коробки (AND/OR/NOT)
- Явная причина отказа
- Легко добавлять новые правила

**Минусы:**
- Классов больше, чем просто if-else в handler
- Нужно изучить паттерн

## Alternatives considered

- **if-else в handler'ах** — отвергнут: плохо тестируется, дублирование правил
- **Policy классы Laravel** — отвергнут: привязаны к user/model, не подходят для сложной композиции
