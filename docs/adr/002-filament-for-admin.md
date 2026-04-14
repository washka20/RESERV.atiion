# ADR 002: Filament для admin panel

**Status:** Accepted
**Date:** 2026-04-14

## Context

Admin-панель требует: CRUD для услуг/категорий/пользователей/бронирований, фильтры, сортировка, роли. Варианты:
1. Кастомный admin на Vue 3 (единый SPA с customer)
2. Filament 3 (Laravel-native admin framework)
3. Laravel Nova (коммерческий)

## Decision

**Filament 3.** Admin живёт на `/admin`, использует session auth Laravel. Отдельно от customer Vue SPA.

## Consequences

**Плюсы:**
- Forms, tables, filters, actions — из коробки, экономия месяцев работы
- Нативная интеграция с Eloquent и Spatie/Permission
- Бесплатный, активно развивается

**Минусы:**
- Tight coupling к Laravel → не портируется в Go (но admin и не нужно портировать)
- Filament ожидает Eloquent — tension с DDD

**Разрешение tension:**
- **Filament read** (таблицы, формы отображения) — Eloquent напрямую (прагматика, admin-view)
- **Filament write** (submit формы, custom actions) — через Command Bus → Domain

## Alternatives considered

- **Vue 3 admin** — отвергнут: месяцы разработки на CRUD
- **Laravel Nova** — отвергнут: коммерческий, Filament лучше по фичам
