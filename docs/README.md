# RESERV.atiion — Documentation

Универсальная букинг-платформа: модульный монолит, DDD + Clean Architecture, Filament (admin) + Vue 3 (customer).

## Структура

- [`architecture/`](architecture/) — архитектура: слои, модули, BC
- [`patterns/`](patterns/) — паттерны: CQRS, Specification, Outbox, Domain Events, Filament bridge
- [`api/`](api/) — API: envelope, auth, endpoints, errors
- [`modules/`](modules/) — документация по модулям (Identity, Catalog, Booking, Payment)
- [`development/`](development/) — setup, testing, contributing, module-guide
- [`deployment/`](deployment/) — docker, ci-cd, environments
- [`adr/`](adr/) — Architecture Decision Records

## Quick links

- [Overview](architecture/overview.md)
- [Setup локально](development/setup.md)
- [ADR index](adr/README.md)

## Patterns

- [CQRS / Domain Events](patterns/domain-events.md)
- [Specification Pattern](patterns/specification-pattern.md)
- [Filament DDD Bridge](patterns/filament-ddd-bridge.md)
- [Outbox Pattern](patterns/outbox-pattern.md)
- [Marketplace Fee](patterns/marketplace-fee.md)

## API

- [Authentication](api/authentication.md)
- [Payouts API](api/payouts.md)

## Modules

- [Identity](modules/identity.md)
- [Catalog](modules/catalog.md)
- [Booking](modules/booking.md)
- [Payment](../backend/app/Modules/Payment/README.md)
