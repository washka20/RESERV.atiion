# Clean Architecture

Внутри каждого модуля — 4 слоя с чётким направлением зависимостей.

## Слои

```
Domain  <-  Application  <-  Infrastructure  ->  Interface
```

**Правило:** стрелки зависимостей направлены внутрь (к Domain).

### Domain (ядро, чистый PHP)
- Entities, Value Objects, Specifications, Domain Services
- Repository Interfaces
- Domain Events
- Domain Exceptions
- **0 зависимостей от Laravel / Eloquent / внешних библиотек**

### Application (use cases)
- Commands/Queries + Handlers
- DTOs
- Application Services (оркестрация)
- Импортирует Domain (интерфейсы)

### Infrastructure (реализации)
- Eloquent Models (с суффиксом `Model`)
- Eloquent Repositories (реализуют интерфейсы Domain)
- Mappers (Domain ↔ Eloquent)
- External services: S3, JWT, outbox worker
- Импортирует Domain + Application

### Interface (точки входа)
- API Controllers, FormRequests, Resources
- Filament Resources, Actions, Pages
- Console Commands
- Импортирует Application (dispatches commands/queries)

## Почему Domain без Laravel

1. **Тестируемость** — unit-тесты без Laravel bootstrap
2. **Портируемость** — Domain переносится в Go 1:1 (учебная цель проекта)
3. **Независимость** — смена фреймворка не ломает ядро

## Проверка правил

Architecture-тесты (Pest arch API):
```php
arch()->expect('App\Modules\*\Domain')
    ->not->toUse('Illuminate');

arch()->expect('App\Modules\*\Application')
    ->not->toUse('Illuminate\Database\Eloquent');
```

Запускаются в CI. Падают при нарушении.

## См. также

- [Modular Monolith](modular-monolith.md)
- [CQRS](../patterns/cqrs.md)
