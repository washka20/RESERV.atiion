# Module Guide — как создать новый модуль

Модули живут в `backend/app/Modules/<Name>/`. Структура фиксирована, проверяется `tests/Architecture/`.

## Шаги

### 1. Создать структуру

```bash
MODULE=MyNewModule
BASE="backend/app/Modules/$MODULE"
mkdir -p \
  "$BASE/Domain/"{Entity,ValueObject,Repository,Service,Event,Specification,Exception} \
  "$BASE/Application/"{Command,Query,DTO,Service} \
  "$BASE/Infrastructure/Persistence/"{Model,Repository,Mapper} \
  "$BASE/Interface/Api/"{Controller,Request,Resource} \
  "$BASE/Interface/Filament/"{Resource,Action,Page} \
  "$BASE/Interface/Console"

find "$BASE" -type d -empty -exec touch {}/.gitkeep \;
```

### 2. Создать `Provider.php`

```php
<?php
declare(strict_types=1);
namespace App\Modules\MyNewModule;

use Illuminate\Support\ServiceProvider;

final class Provider extends ServiceProvider
{
    public function register(): void
    {
        // $this->app->bind(FooRepositoryInterface::class, EloquentFooRepository::class);
    }

    public function boot(): void
    {
        // ModuleEventSubscriber::register(...);
    }
}
```

### 3. Зарегистрировать в `ModuleServiceProvider`

Добавить FQCN в `MODULE_PROVIDERS` (`backend/app/Providers/ModuleServiceProvider.php`):

```php
private const MODULE_PROVIDERS = [
    // ...
    \App\Modules\MyNewModule\Provider::class,
];
```

### 4. Создать `README.md` модуля

Скопировать шаблон из любого существующего (`app/Modules/Identity/README.md`), заполнить секции:
- Purpose, Aggregates, Value Objects, Domain Events, Specifications
- API endpoints, Filament Resources
- Dependencies (через Shared interfaces / Events)

### 5. Architecture-тесты

В `backend/tests/Architecture/ModuleIsolationTest.php` добавить блок для нового модуля (запрет импорта остальных).

### 6. Документация проекта

- `docs/modules/<name>.md` — описание BC для внешних читателей
- Если BC значимый — ADR (`docs/adr/NNN-*.md`)

## Что ЗАПРЕЩЕНО

- Прямой `use App\Modules\OtherModule\Domain\*` из вашего модуля
- `use Illuminate\*` в `Domain/`
- `use Illuminate\Database\Eloquent\*` в `Application/`
- Бизнес-логика в `Interface/` (Controller, FilamentResource, Request)

Все запреты проверяются `tests/Architecture/`.

## См. также
- [Modular Monolith](../architecture/modular-monolith.md)
- [Clean Architecture](../architecture/clean-architecture.md)
