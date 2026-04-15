# Catalog Module

Покрывает `services`, `categories`, `subcategories`. Детали: [docs/modules/catalog.md](../../../../docs/modules/catalog.md).

## Layout

- `Domain/` — агрегаты (`Service` / `Category` / `Subcategory`), VO (`Money` / `Duration` / `ServiceType` / `ImagePath` / `*Id`), events, specifications, repository interfaces, exceptions. Zero `Illuminate\*` imports.
- `Application/Command/<Name>/` — `<Name>Command` + `<Name>Handler` (auto-resolved CommandBus by convention).
- `Application/Query/<Name>/` — `<Name>Query` + `<Name>Handler`, возвращает DTO через `DB::table` (без Eloquent).
- `Application/DTO/` — readonly классы для envelope / query output.
- `Infrastructure/Persistence/` — Eloquent Models + Mappers + Repositories (impl интерфейсов из Domain).
- `Interface/Api/` — Controllers + FormRequests + Resource-арреи + `routes.php`.
- `Interface/Filament/` — Resource + Pages + Actions + RelationManagers.

## Правила

- **Write** (изменения состояния) → через CommandBus + domain aggregate + repository
- **Read** (queries) → `DB::table()` → DTO (НЕ Eloquent)
- Eloquent разрешён только в `Infrastructure/` (Models + Repositories) и Filament (read-side таблицы / формы)
- Domain не импортирует `Illuminate\*`

## Запуск тестов модуля

```bash
docker compose -f docker-compose.yml -f docker-compose.dev.yml exec -T php \
    ./vendor/bin/pest \
        tests/Unit/Modules/Catalog \
        tests/Feature/Modules/Catalog \
        tests/Feature/Api/Catalog \
        tests/Feature/Filament/Catalog
```

## Добавить новый Command

1. Создать `Application/Command/<Name>/<Name>Command.php` (`final readonly class` с public properties)
2. Создать `Application/Command/<Name>/<Name>Handler.php` — `final class`, ctor DI на repository interfaces + `DomainEventDispatcherInterface`
3. CommandBus auto-resolves по naming convention (`FooCommand` → `FooHandler` в том же namespace) — регистрация в `Provider` не нужна
4. Unit-тест в `tests/Unit/Modules/Catalog/Application/` (мокай repos + event dispatcher)

## Добавить новое Query

1. Создать `Application/Query/<Name>/<Name>Query.php`
2. Создать `Application/Query/<Name>/<Name>Handler.php` — инжектит `Illuminate\Database\DatabaseManager` ИЛИ использует `DB` facade
3. Возвращает DTO из `Application/DTO/`
4. Integration-тест в `tests/Feature/Modules/Catalog/` с `RefreshDatabase`
