# Modular Monolith

Модульный монолит — одно Laravel-приложение, внутри которого **строгие границы между модулями**. Общение только через Domain Events или явные интерфейсы.

## Почему не микросервисы

- Один разработчик / небольшая команда
- Один деплой проще N деплоев
- Границы в коде проще, чем границы по сети
- Выделение микросервисов — когда конкретный модуль упрётся в масштаб

## Почему не обычный монолит

- Бизнес-логика разделяется по BC с чёткими границами
- Рефакторинг внутри модуля не ломает другие
- Легко извлечь модуль в сервис, когда понадобится

## Структура

```
backend/app/
├── Modules/
│   ├── Identity/
│   │   ├── Domain/
│   │   ├── Application/
│   │   ├── Infrastructure/
│   │   ├── Interface/
│   │   └── Provider.php
│   ├── Catalog/
│   ├── Booking/
│   └── Payment/
└── Shared/
    ├── Domain/
    ├── Application/
    └── Infrastructure/
```

## Правила межмодульного общения

1. **Domain Events** (предпочтительно) — модуль публикует, другие подписываются
2. **Application Service Interfaces** в Shared — когда нужен синхронный запрос
3. **НЕЛЬЗЯ** — прямой импорт `App\Modules\X\Domain\*` из `App\Modules\Y\*`

Проверяется architecture-тестами (Pest arch API).

## Пример: Booking → Payment

Booking публикует `BookingCreated`. Payment слушает и инициирует платёж. Нет прямых вызовов.

## См. также

- [Bounded Contexts](bounded-contexts.md)
- [Domain Events](../patterns/domain-events.md)
- [ADR 001](../adr/001-modular-monolith.md)
