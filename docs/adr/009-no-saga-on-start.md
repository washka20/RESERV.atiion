# ADR 009: Без Saga на старте

**Status:** Accepted
**Date:** 2026-04-14

## Context

Booking flow: validate → reserve slot → charge payment → confirm → notify. Это цепочка шагов, которая может упасть на середине. Saga — паттерн для таких процессов.

## Decision

**Без Saga.** Для синхронных flow используем Laravel Pipeline + DB транзакции. Компенсации — внутри handler'ов (в транзакции либо через domain events).

## Consequences

**Плюсы:**
- Проще разработка
- Транзакции PostgreSQL дают гарантии

**Минусы:**
- Не подходит для кросс-сервисных сценариев
- Нет формальных компенсаций

**Когда добавить Saga:**
- Payment выделен в отдельный сервис
- Появятся длительные async-процессы (> нескольких секунд)

## Alternatives considered

- **Saga через stream events (spatie/laravel-stateful-jobs)** — отвергнут: преждевременно, один сервис
