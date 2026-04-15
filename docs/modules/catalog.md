# Catalog Module

> Bounded Context: каталог услуг с типизацией (TIME_SLOT / QUANTITY)

## Purpose

Catalog — управляет услугами (`services`), категориями (`categories`) и подкатегориями (`subcategories`). Универсальная букинг-платформа поддерживает два типа услуг:

- **TIME_SLOT** — бронирование на конкретный временной слот, требует `Duration` (длительность услуги в минутах)
- **QUANTITY** — бронирование N единиц на диапазон дат, требует `totalQuantity` (общее количество)

Модуль публикует domain events при создании/обновлении/активации/деактивации услуг — будущий Booking module слушает их для проверки доступности.

## Aggregates / Entities

- **Service** (aggregate root, `Domain/Entity/Service.php`) — id, name, description, price (`Money`), type (`ServiceType`), duration (`?Duration`), totalQuantity (`?int`), categoryId, subcategoryId, images (`ImagePath[]`), isActive
- **Category** (aggregate root, `Domain/Entity/Category.php`) — id, name, slug, sortOrder, subcategories (child collection)
- **Subcategory** (child entity, `Domain/Entity/Subcategory.php`) — id, categoryId, name, slug, sortOrder. Не aggregate root: жизненный цикл управляется через Category.

## Value Objects

- `ServiceId`, `CategoryId`, `SubcategoryId` — extends `Shared/Domain/AggregateId` (UUID v7)
- `Money` — amount (cents) + currency (RUB | USD | EUR), валидация ≥ 0
- `Duration` — minutes (positive int), `fromMinutes()` factory
- `ServiceType` — backed enum (`TIME_SLOT` | `QUANTITY`) с методами `requiresDuration()` / `requiresTotalQuantity()`
- `ImagePath` — относительный путь файла, защита от path traversal (`..`, абсолютные пути отклоняются)

## Инвариант типизации услуги

`Service::assertInvariants()` проверяет в конструкторе и фабричных методах:

- `type === TIME_SLOT` ⇒ `duration !== null`, `totalQuantity === null`
- `type === QUANTITY` ⇒ `totalQuantity > 0`, `duration === null`

Нарушение бросает `InvalidServiceTypeException`. Дублируется на уровне БД через PostgreSQL `CHECK` constraints в миграции `services` (SQLite не enforces — поэтому Domain — единственный источник правды для тестов на SQLite).

## Domain Events

**Публикуемые:**

- `ServiceCreated(ServiceId, name, ServiceType, occurredAt)` — после `Service::create()`
- `ServiceUpdated(ServiceId, occurredAt)` — после `Service::update*()` (price/description/category)
- `ServiceActivated(ServiceId, occurredAt)` — после `Service::activate()` (идемпотентно: если уже active — событие не emit)
- `ServiceDeactivated(ServiceId, occurredAt)` — после `Service::deactivate()` (идемпотентно)
- `CategoryCreated(CategoryId, slug, occurredAt)` — после `Category::create()`

**Слушаемые:** нет на текущий момент.

**Будущие потребители (Plan 7+):** Booking module — слушает `ServiceDeactivated` для отмены будущих pending-бронирований; перед созданием booking проверяет `ServiceIsActive` спецификацию.

## Specifications

- `ServiceIsActive` — `Service::isActive() === true`
- `ServiceHasSufficientInfo` — name not empty, price > 0, type-specific поля заполнены

Композируются через `AndSpecification` / `OrSpecification` / `NotSpecification` из Shared kernel.

## Commands (CQRS write через CommandBus)

| Command | Handler emits | Бросает |
|---------|---------------|---------|
| `CreateServiceCommand` | `ServiceCreated` | `InvalidServiceTypeException`, `CategoryNotFoundException` |
| `UpdateServiceCommand` | `ServiceUpdated` | `ServiceNotFoundException` |
| `ActivateServiceCommand` | `ServiceActivated` | `ServiceNotFoundException` |
| `DeactivateServiceCommand` | `ServiceDeactivated` | `ServiceNotFoundException` |
| `AddServiceImageCommand` | `ServiceUpdated` | `ServiceNotFoundException` |
| `RemoveServiceImageCommand` | `ServiceUpdated` | `ServiceNotFoundException` |
| `CreateCategoryCommand` | `CategoryCreated` | — |
| `UpdateCategoryCommand` | — | `CategoryNotFoundException` |
| `DeleteCategoryCommand` | — | `CategoryHasServicesException`, `CategoryNotFoundException` |
| `CreateSubcategoryCommand` | — | `CategoryNotFoundException` |
| `UpdateSubcategoryCommand` | — | `CategoryNotFoundException` |
| `DeleteSubcategoryCommand` | — | `CategoryNotFoundException` |

Auto-resolution: `CreateServiceCommand` → `CreateServiceHandler` в том же namespace (см. [docs/patterns/cqrs.md](../patterns/cqrs.md)).

## Queries (CQRS read через QueryBus)

Все query handlers используют `DB::table()` и возвращают DTO — **без Eloquent**. См. [ADR-007](../adr/007-read-side-without-eloquent.md).

| Query | Параметры | DTO |
|-------|-----------|-----|
| `ListServicesQuery` | categoryId?, subcategoryId?, type?, search?, minPrice?, maxPrice?, page, perPage | `ServiceListItemDTO[]` + meta pagination |
| `GetServiceQuery` | id | `ServiceDTO` (с images[]) |
| `ListCategoriesQuery` | — | `CategoryDTO[]` (с nested `SubcategoryDTO[]`) |
| `GetCategoryBySlugQuery` | slug | `CategoryDTO` |

## API endpoints (prefix `/api/v1`)

| Метод | URL | Auth | Результат |
|-------|-----|------|-----------|
| GET | `/services` | — | 200 + `ServiceListItem[]` + pagination meta |
| GET | `/services/{id}` | — | 200 + полный `Service` DTO |
| GET | `/categories` | — | 200 + `Category[]` с subcategories |
| GET | `/categories/{slug}` | — | 200 + `Category` |

### Query params для `GET /services`

| Param | Тип | Валидация |
|-------|-----|-----------|
| `categoryId` | UUID | nullable |
| `subcategoryId` | UUID | nullable |
| `type` | `time_slot` \| `quantity` | nullable, enum |
| `search` | string | nullable, max 100 |
| `minPrice` / `maxPrice` | int (копейки) | nullable, ≥ 0 |
| `page` | int | ≥ 1, default 1 |
| `perPage` | int | 1..100, default 20 |

### Envelope (успех)

```json
{
  "success": true,
  "data": [
    { "id": "...", "name": "...", "price": { "amount": 150000, "currency": "RUB" }, "type": "time_slot", "...": "..." }
  ],
  "error": null,
  "meta": { "page": 1, "per_page": 20, "total": 47, "last_page": 3 }
}
```

### Error codes

| Код | HTTP | Когда |
|-----|------|-------|
| `SERVICE_NOT_FOUND` | 404 | `GET /services/{id}` с несуществующим id |
| `CATEGORY_NOT_FOUND` | 404 | `GET /categories/{slug}` с несуществующим slug |
| `VALIDATION_ERROR` | 422 | Невалидные query params |

## Filament Resources (`/admin`)

- `Interface/Filament/Resource/CategoryResource` + Pages (List / Create / Edit) — CRUD категорий, RelationManager для subcategories
- `Interface/Filament/Resource/ServiceResource` + Pages (List / Create / Edit / View)
  - Form: name, description, category, subcategory (cascading), price (Money input), type (Select), conditional fields:
    - `duration_minutes` — visible if `type === time_slot`
    - `total_quantity` — visible if `type === quantity`
  - Table: id, name, category, type (badge), price, isActive (toggle), created_at
  - Filters: category, subcategory, type, isActive
- Custom actions:
  - `ActivateServiceAction` — диспатчит `ActivateServiceCommand`
  - `DeactivateServiceAction` — диспатчит `DeactivateServiceCommand`
- Все writes идут через CommandBus (`handleRecordCreation` / `handleRecordUpdate`). Read через Eloquent Models напрямую — см. [`.claude/rules/filament.md`](../../.claude/rules/filament.md).

## Frontend (Vue SPA)

### Routes

| Path | View | data-test-id главного контейнера |
|------|------|----------------------------------|
| `/catalog` | `CatalogView` | `catalog-page` |
| `/catalog/:id` | `ServiceDetailView` | `catalog-service-detail` |
| `/booking/new` | `BookingStubView` (заглушка до Plan 7) | — |

### data-test-id (для E2E)

- `catalog-page`, `catalog-service-card`, `catalog-service-card-link`
- `catalog-category-filter-select`, `catalog-type-filter-select`, `catalog-search-input`
- `catalog-price-filter-min`, `catalog-price-filter-max`
- `catalog-pagination-prev`, `catalog-pagination-next`
- `catalog-service-detail`, `service-detail-book-btn`

## Dependencies

**На другие модули:** нет прямых импортов (modular monolith — см. [ADR-001](../adr/001-modular-monolith.md)).

**От других модулей ожидается (события):** Booking module (Plan 7) будет потреблять `ServiceDeactivated` и читать данные через query handlers.

**Identity:** только косвенно — admin auth для `/admin` (Filament `canAccessPanel` проверяет роли `admin` / `manager` через Spatie).

**Shared kernel:**
- `Shared/Domain/AggregateRoot`, `AggregateId`, `DomainEvent`, `Specification`
- `Shared/Application/Bus/CommandBusInterface`, `QueryBusInterface`
- `Shared/Application/Event/DomainEventDispatcherInterface`

## Тесты

- Unit: `backend/tests/Unit/Modules/Catalog/` — Domain VO + Entities + Specifications + Application command handlers (Mockery)
- Feature (DB): `backend/tests/Feature/Modules/Catalog/` — Eloquent repositories, Mappers, Query handlers
- API: `backend/tests/Feature/Api/Catalog/` — HTTP flow, envelope, фильтры, pagination
- Filament: `backend/tests/Feature/Filament/Catalog/` — CRUD через Livewire, custom actions
- E2E: `frontend/e2e/spec/catalog/browse.spec.ts` — список → фильтр → деталь

## Ссылки

- Код: [`backend/app/Modules/Catalog/`](../../backend/app/Modules/Catalog/)
- Module README: [`backend/app/Modules/Catalog/README.md`](../../backend/app/Modules/Catalog/README.md)
- ADR-001 modular monolith: [`docs/adr/001-modular-monolith.md`](../adr/001-modular-monolith.md)
- ADR-007 read side without Eloquent: [`docs/adr/007-read-side-without-eloquent.md`](../adr/007-read-side-without-eloquent.md)
- CQRS pattern: [`docs/patterns/cqrs.md`](../patterns/cqrs.md)
