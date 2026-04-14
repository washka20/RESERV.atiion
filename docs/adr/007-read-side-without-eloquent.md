# ADR 007: Read-side customer API без Eloquent

**Status:** Accepted
**Date:** 2026-04-14

## Context

CQRS-подход: command-side меняет состояние через Domain + Eloquent. Query-side отвечает на запросы клиента. Варианты для read-side:
1. Использовать те же Eloquent-модели и relations
2. Отдельные read-модели через `DB::table()` → DTO
3. Отдельная read-база (event sourcing projection)

## Decision

**`DB::table()` → DTO для customer API.** Query handlers используют Laravel Query Builder, возвращают DTO. Без Eloquent.

Filament остаётся на Eloquent (это admin-view, свой путь).

## Consequences

**Плюсы:**
- Быстрее Eloquent (нет hydration, events, dirty tracking)
- Явные SQL-запросы, контроль N+1
- Чистое разделение write/read

**Минусы:**
- Больше кода (маппинг row → DTO вручную)
- Нет Eloquent relations в read-side

## Alternatives considered

- **Eloquent для read** — отвергнут: медленнее, ORM overhead
- **Отдельная read-база** — отвергнут: преждевременно, нет нагрузки
