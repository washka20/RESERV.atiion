# Module Guide — как создать новый модуль

> **Новый в проекте?** Сначала прочитай [DDD Onboarding](ddd-onboarding.md) — 1 страница, что можно/нельзя.

Модули живут в `backend/app/Modules/<Name>/`. Структура фиксирована **по реальному содержимому** (см. ADR-016): создаём только те папки, для которых есть классы.

## Шаги

### 1. Создать baseline-структуру

```bash
MODULE=MyNewModule
BASE="backend/app/Modules/$MODULE"
mkdir -p \
  "$BASE/Domain/"{Entity,ValueObject} \
  "$BASE/Application/"{Command,Query,DTO} \
  "$BASE/Infrastructure/Persistence" \
  "$BASE/Interface/"{Api,Filament,Console}

find "$BASE" -type d -empty -exec touch {}/.gitkeep \;
```

**Baseline минимум** — Entity и ValueObject в Domain, Command/Query/DTO в Application. Остальное создаётся когда появляется первый класс:

| Папка | Когда создавать |
|---|---|
| `Domain/Event` | Когда aggregate начинает публиковать Domain Event |
| `Domain/Exception` | Когда появляется первое доменное исключение (не generic) |
| `Domain/Repository` | Когда появляется первый RepositoryInterface |
| `Domain/Service` | Когда логика не принадлежит ни одной entity (редко!) |
| `Domain/Specification` | Когда правило **используется из >2 мест** или инкапсулирует сложную причину провала (см. ADR-016) |
| `Domain/Gateway` | Интерфейсы внешних систем (payment gateway, SMS provider) |
| `Infrastructure/Persistence/{Model,Repository,Mapper}` | При первом Eloquent-mapping |
| `Application/Service` | Когда есть оркестрация не-CQRS (AuthService и т.п.) |
| `Application/Listener` | Когда модуль подписывается на чужие Domain Events |
| `Interface/Api/{Controller,Request,Resource}` | При первом endpoint |
| `Interface/Filament/{Resource,Action,Page}` | При первом admin-экране |

**Не создавать пустые папки «на вырост»** — это вводит в заблуждение и считается технической грязью (ревью отвергнет).

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

### 3. Provider подхватится автоматически

`ModuleServiceProvider` сканирует `app/Modules/*/Provider.php` через `glob()`. Положил файл по конвенции — он зарегистрируется. Правки `MODULE_PROVIDERS` **не нужны** (массив удалён).

Так же работает `routes/api_v1.php` — он авто-подхватит `app/Modules/<Module>/Interface/Api/routes.php` если файл существует.

### 4. Создать `README.md` модуля

Скопировать шаблон из любого существующего (`app/Modules/Identity/README.md`), заполнить секции:
- Purpose, Aggregates, Value Objects, Domain Events
- API endpoints, Filament Resources
- Dependencies (через Shared interfaces / Events)

Секцию Specifications писать **только если Specification-классы реально используются**.

### 5. Architecture-тесты

В `backend/tests/Architecture/ModuleIsolationTest.php` добавить блок для нового модуля (запрет импорта остальных).

### 6. Документация проекта

- `docs/modules/<name>.md` — описание BC для внешних читателей
- Если BC значимый — ADR (`docs/adr/NNN-*.md`)

## Read-side в Query handlers

По ADR-016 новое default — **Eloquent с `::with() + Resource`** для простых `get by id` / `list with filters`:

```php
public function handle(GetFooQuery $query): FooDTO
{
    $model = FooModel::with('bar')->findOrFail($query->id);
    return FooDTO::fromModel($model);
}
```

`DB::table() → DTO` использовать **только** когда:
- Запрос делает сложные агрегации (GROUP BY, JOIN с подсчётами, которые Eloquent relations провоцируют в N+1).
- Профилирование показало проблему.

Существующие 21 handler с `DB::table()` (Booking, Catalog, Identity, Payment) НЕ мигрируем — переход только для новых.

## Specification pattern

По ADR-016 — используется **точечно**. Если правило нужно один раз в одном handler'е — сделай его методом Entity или private helper в handler'е:

```php
// Правильно: метод на Entity
final class Service {
    public function isActive(): bool { return $this->status === ServiceStatus::ACTIVE; }
}

// Правильно: facade-Policy с ручной композицией
final class BookingPolicy {
    public function check(Booking $b, int $userActiveBookings): void {
        if (!$this->withinWindow->isSatisfiedBy($b)) throw ...;
        if (!$this->userLimit->isSatisfiedBy(['userActiveBookings' => $userActiveBookings])) throw ...;
    }
}

// Избыточно: Specification с 0 композицией и одним usage
final class ServiceIsActive extends Specification { ... }  // <— просто метод Entity
```

## Что ЗАПРЕЩЕНО

- Прямой `use App\Modules\OtherModule\Domain\*` из вашего модуля
- `use Illuminate\*` в `Domain/`
- `use Illuminate\Database\Eloquent\*` в `Application/`
- Бизнес-логика в `Interface/` (Controller, FilamentResource, Request)
- Создание пустых папок «на вырост» (см. ADR-016)

Запреты проверяются `tests/Architecture/`.

## См. также
- [Modular Monolith](../architecture/modular-monolith.md)
- [Clean Architecture](../architecture/clean-architecture.md)
- [ADR-016: DDD pragmatic scope](../adr/016-ddd-pragmatic-scope.md)
