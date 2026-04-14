# Architecture Overview

RESERV.atiion — **модульный монолит** на Laravel 13. Backend serves **REST API** (для Vue 3 SPA) и **Filament admin** (server-rendered). Frontend — Vue 3 SPA, отдельный от backend, общается через JWT-авторизованный API.

## Высокоуровневая диаграмма

```
+-----------------+        +--------------------------+        +-------------+
| Customer (Vue 3)| <----> | /api/v1 (JWT)            |        |             |
+-----------------+        |                          |        |             |
                           |  Laravel 13 backend      | <----> | PostgreSQL  |
+-----------------+        |  (modular monolith)      |        |             |
| Admin (Filament)| <----> | /admin (session)         |        +-------------+
+-----------------+        +--------------------------+
                                     |
                                     v
                           +--------------------------+
                           | Redis (cache, queues)    |
                           | S3 / MinIO (media)       |
                           +--------------------------+
```

## Модули (bounded contexts)

- **Identity** — аутентификация, пользователи, роли
- **Catalog** — категории, подкатегории, услуги (типизация: time slot / quantity)
- **Booking** — бронирования, слоты, проверка доступности (core)
- **Payment** — интерфейсы оплаты (реализация позже)

Модули общаются через **Domain Events** или явные **Application Service Interfaces** (в Shared). Никаких прямых вызовов Domain/Repository другого модуля.

## Слои (Clean Architecture)

Внутри каждого модуля — 4 слоя. См. [clean-architecture.md](clean-architecture.md).

## Ссылки

- [Modular Monolith](modular-monolith.md)
- [Clean Architecture](clean-architecture.md)
- [Bounded Contexts](bounded-contexts.md)
- [ADR 001: Модульный монолит](../adr/001-modular-monolith.md)
