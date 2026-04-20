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
| [004](004-specification-pattern.md) | Specification Pattern для бизнес-правил | Superseded in parts by ADR-016 |
| [005](005-outbox-pattern.md) | Outbox Pattern (селективно) | Accepted |
| [006](006-legacy-to-old-folder.md) | Легаси-код в `old/` | Accepted |
| [007](007-read-side-without-eloquent.md) | Read-side customer API без Eloquent | Superseded in parts by ADR-016 |
| [008](008-no-event-sourcing.md) | Без Event Sourcing на старте | Accepted |
| [009](009-no-saga-on-start.md) | Без Saga на старте | Accepted |
| [010](010-spatie-permission-with-domain-roles.md) | Spatie Permission с доменными ролями | Accepted |
| [011](011-booking-concurrency-strategy.md) | Booking concurrency strategy | Accepted |
| [012](012-organizations-memberships.md) | Organizations + Memberships (marketplace identity) | Accepted |
| [013](013-marketplace-fee.md) | Marketplace fee 10% (flat) с банкерским округлением | Accepted |
| [014](014-design-tokens.md) | Design tokens (OKLCH) | Accepted |
| [015](015-jwt-storage-strategy.md) | JWT storage: localStorage (Phase 1) → httpOnly cookie (Phase 2) | Accepted |
| [016](016-ddd-pragmatic-scope.md) | DDD pragmatic scope — упрощение после Plan 14 | Accepted |
