# RESERV.atiion

Универсальная букинг-платформа с drag-and-drop конструктором лендингов.

## Features

- **Универсальное бронирование:**
  - Временные слоты (стрижка, консультация, аренда зала)
  - Штучные (номера в отеле, оборудование, билеты)
- **Админка** на Filament 3
- **Customer SPA** на Vue 3
- **Оплата** (архитектура заложена, реализация в процессе)
- **Конструктор лендингов** (отдельная репа как git submodule)

## Стек

- **Backend:** Laravel 13 / PHP 8.4 / PostgreSQL 17 / Redis 7 / S3
- **Admin:** Filament 3
- **Frontend:** Vue 3 + TypeScript + Pinia + Vue Router + Tailwind CSS
- **Infra:** Docker Compose, GitHub Actions

## Архитектура

**Модульный монолит** с DDD + Clean Architecture. Каждый bounded context — отдельный модуль в `backend/app/Modules/`.

- `Identity` — аутентификация
- `Catalog` — услуги и категории
- `Booking` — бронирования (core)
- `Payment` — оплата (интерфейсы)

Подробнее: [`docs/architecture/overview.md`](docs/architecture/overview.md).

## Quickstart

```bash
git clone <repo-url>
cd RESERV.atiion

make up        # поднять Docker Compose
make migrate   # миграции
make seed      # сиды (создаст admin@example.com / password123)
```

Открыть:
- Customer SPA: http://localhost:5173
- Admin Filament: http://localhost:8080/admin
- API: http://localhost:8080/api/v1

Детально: [`docs/development/setup.md`](docs/development/setup.md).

## Документация

- [`docs/`](docs/) — вся техническая документация
- [`docs/architecture/`](docs/architecture/) — архитектура
- [`docs/adr/`](docs/adr/) — ключевые решения
- [`docs/patterns/`](docs/patterns/) — паттерны (CQRS, Specification, Outbox)
- [`docs/api/`](docs/api/) — API reference
- [`docs/modules/`](docs/modules/) — документация модулей

## Разработка

- [`docs/development/setup.md`](docs/development/setup.md) — setup
- [`docs/development/testing.md`](docs/development/testing.md) — тесты
- [`docs/development/contributing.md`](docs/development/contributing.md) — конвенции

## License

TBD
