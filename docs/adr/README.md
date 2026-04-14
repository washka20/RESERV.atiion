# Architecture Decision Records

ADR документируют ключевые архитектурные решения, их контекст и последствия.

## Формат

Каждый ADR:
- **Title** — краткое название решения
- **Status** — Accepted / Superseded / Deprecated
- **Context** — почему возникла проблема
- **Decision** — что решили
- **Consequences** — следствия (плюсы, минусы, компромиссы)
- **Alternatives considered** — что рассматривали и почему отвергли

## Index

| # | Title | Status |
|---|-------|--------|
| [001](001-modular-monolith.md) | Модульный монолит | Accepted |
| [002](002-filament-for-admin.md) | Filament для admin panel | Accepted |
| [003](003-jwt-customer-session-admin.md) | JWT для customer API, session для admin | Accepted |
| [004](004-specification-pattern.md) | Specification Pattern для бизнес-правил | Accepted |
| [005](005-outbox-pattern.md) | Outbox Pattern (селективно) | Accepted |
| [006](006-legacy-to-old-folder.md) | Легаси-код в `old/` | Accepted |
| [007](007-read-side-without-eloquent.md) | Read-side customer API без Eloquent | Accepted |
| [008](008-no-event-sourcing.md) | Без Event Sourcing на старте | Accepted |
| [009](009-no-saga-on-start.md) | Без Saga на старте | Accepted |
