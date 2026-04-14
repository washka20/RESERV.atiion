# ADR 003: JWT для customer API, session для admin

**Status:** Accepted
**Date:** 2026-04-14

## Context

Два разных клиента backend'а:
- Customer Vue 3 SPA (может быть на другом домене, mobile app в будущем)
- Filament admin (серверно-рендеренный Laravel)

Нужно выбрать auth-стратегию.

## Decision

**Двойная система:**
- Customer API (`/api/v1/*`) — JWT (access + refresh tokens)
- Admin (`/admin/*`) — Laravel session auth (нативный для Filament)

Разные guards в `config/auth.php`:
- `api` — JWT guard (для customer)
- `web` — session guard (для admin)

## Consequences

**Плюсы:**
- JWT: stateless, подходит для SPA на другом домене и mobile
- Session: Filament работает из коробки, CSRF автоматом

**Минусы:**
- Два пути auth — больше тестов
- Refresh token flow добавляет сложности

## Alternatives considered

- **Только JWT для admin** — отвергнут: Filament требует session, переделывать Filament под JWT = часами
- **Только session для API** — отвергнут: SPA на другом домене требует CORS + CSRF неудобства; mobile app нуждается в JWT
