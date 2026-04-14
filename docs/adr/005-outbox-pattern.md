# ADR 005: Outbox Pattern (селективно)

**Status:** Accepted
**Date:** 2026-04-14

## Context

Domain Events запускают downstream действия: уведомления, интеграции, платежи. Проблема: если событие отправлено, но downstream упал — событие потеряно. Особенно критично для Payment.

## Decision

**Outbox Pattern только для критичных событий:**
- `PaymentReceived`, `PaymentRefunded`, `PaymentFailed`
- `BookingConfirmed` (downstream: email, landing webhooks)
- `BookingCancelled` (downstream: release slot, refund)

Остальные события (analytics, internal notifications) — обычные Laravel Queues.

### Реализация

1. В транзакции: сохраняется доменное состояние + запись в `outbox_messages`
2. Воркер (`OutboxWorker`) читает pending → публикует в Queue/webhook → помечает published
3. Retry с exponential backoff

## Consequences

**Плюсы:**
- Гарантия доставки критичных событий (at-least-once)
- Нет потери платежей

**Минусы:**
- Доп. таблица + воркер
- Сложнее локальная отладка

## Alternatives considered

- **Без Outbox** — отвергнут: риск потери платёжных событий
- **Outbox для всех событий** — отвергнут: избыточно для analytics
- **Transactional Outbox через Debezium** — отвергнут: оверкилл для одной базы
