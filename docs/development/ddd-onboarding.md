# DDD Onboarding — 1-page шпаргалка

Для разработчиков, которые заходят в проект впервые. Цель — **не запороть архитектуру**, даже если DDD видишь в первый раз. Если сомневаешься — спрашивай в PR, не в production.

## Куда класть код — быстрая шпаргалка

| Что пишешь | Куда |
|---|---|
| Новая модель данных (бронь, услуга, пользователь) | `app/Modules/<Module>/Domain/Entity/` |
| Маленький тип (Email, Money, Slug, Status) | `app/Modules/<Module>/Domain/ValueObject/` |
| Функция записи — create/update/delete | `app/Modules/<Module>/Application/Command/<Action>/` (Command + Handler) |
| Функция чтения — get/list для API | `app/Modules/<Module>/Application/Query/<Action>/` (Query + Handler + DTO) |
| Eloquent Model | `app/Modules/<Module>/Infrastructure/Persistence/Model/` |
| Repository реализация | `app/Modules/<Module>/Infrastructure/Persistence/Repository/` |
| Repository **интерфейс** | `app/Modules/<Module>/Domain/Repository/` |
| HTTP контроллер | `app/Modules/<Module>/Interface/Api/Controller/` |
| Filament Resource | `app/Modules/<Module>/Interface/Filament/Resource/` |
| Доменное событие (UserRegistered, BookingCreated) | `app/Modules/<Module>/Domain/Event/` |
| Доменное исключение | `app/Modules/<Module>/Domain/Exception/` |

## Три правила, которые нельзя нарушать

Эти правила проверяются автотестами в `tests/Architecture/`. Нарушишь — CI покажет красный.

### 1. `Domain/` не импортирует Laravel

```php
// ❌ НЕЛЬЗЯ
namespace App\Modules\Booking\Domain\Entity;
use Illuminate\Database\Eloquent\Model;  // <-- вылет

// ✅ МОЖНО
namespace App\Modules\Booking\Domain\Entity;
use App\Shared\Domain\AggregateRoot;  // Shared\Domain тоже чистый PHP
```

Domain — чистый PHP. Если нужен UUID — бери из Ramsey. Если нужен Carbon — нет, используй `\DateTimeImmutable`.

### 2. `Application/` не использует Eloquent

```php
// ❌ НЕЛЬЗЯ
namespace App\Modules\Booking\Application\Command\CreateBooking;
public function handle(CreateBookingCommand $cmd): void {
    $booking = BookingModel::create([...]);  // <-- вылет
}

// ✅ МОЖНО
public function handle(CreateBookingCommand $cmd): void {
    $booking = Booking::create(...);  // Domain entity
    $this->bookingRepository->save($booking);  // через интерфейс
}
```

Application знает только про Domain entity + Repository интерфейсы. Eloquent прячется в `Infrastructure/Persistence/Repository/`.

### 3. `Application/Command/*` и `Application/Service/*` не используют `DB::table`

`DB::` разрешён **только в Query handlers** (read-side по ADR-016).

```php
// ❌ НЕЛЬЗЯ в CreateBookingHandler
DB::table('bookings')->insert([...]);  // raw SQL в write-side = обход Domain

// ✅ МОЖНО в Query/ListBookingsHandler
$rows = DB::table('bookings')->where(...)->get();
return BookingDTO::fromRow($rows);
```

## CQRS-lite: write vs read

**Write (Command):** меняет состояние. Идёт через Domain.
```
Controller → CommandBus → Handler → Entity.doSomething() → Repository.save()
```

**Read (Query):** возвращает данные клиенту. Может идти напрямую в БД через Eloquent или `DB::table`.
```
Controller → QueryBus → Handler → DB::table/Eloquent → DTO → Resource
```

По ADR-016: **новые Query handler'ы пиши через Eloquent + Resource**, не через `DB::table`. `DB::table` → DTO оставляем для сложных агрегаций (payouts, availability). Существующие 21 handler с `DB::table` не трогаем.

## Межмодульное общение

**Разрешено:**
1. **Domain Events** — модуль публикует, другой модуль слушает. Пример: `BookingConfirmed` → Payment Listener создаёт PayoutTransaction.
2. **Shared-интерфейсы** для синхронных запросов. Пример: `PaymentGatewayInterface` в `Shared/` — Booking вызывает его без знания реализации.

**Запрещено:**
- Прямой `use App\Modules\OtherModule\Domain\*` из вашего модуля. Все кросс-модульные импорты — через `App\Shared\*` или через Events.

## Общие анти-паттерны

| Анти-паттерн | Правильно |
|---|---|
| Бизнес-логика в контроллере | Контроллер тонкий: auth → validation → CommandBus->dispatch → Resource |
| Валидация в Controller.php | В `FormRequest::rules()` |
| `Eloquent::where()` в Handler | Через `RepositoryInterface` |
| `Booking::create()` (Eloquent) в Handler | `$this->bookingRepo->save(Booking::factory(...))` |
| God-сервис `BookingService` с 20 методами | Один Handler на одну команду |
| Новый модуль без `Provider.php` | `Modules/<X>/Provider.php` — подхватится авто-discovery |
| Пустые папки `Domain/Specification/` «на вырост» | Создавай когда первый класс появится (см. ADR-016) |

## Если сомневаешься — смотри примеры

- Полный чистый модуль: `app/Modules/Identity/` (auth, register)
- Модуль с dual-type aggregate: `app/Modules/Booking/` (TIME_SLOT vs QUANTITY)
- Модуль с интеграцией внешних систем: `app/Modules/Payment/` (Gateway интерфейс + Stub)

## Что читать дальше

1. **ADR-001** — зачем модульный монолит вообще
2. **ADR-016** — какие упрощения сделали в 2026-04-20 (текущие правила)
3. `docs/architecture/clean-architecture.md` — слои с диаграммой
4. `docs/patterns/cqrs.md` — детали CQRS
5. `.claude/rules/ddd.md` — полный список правил проекта

## Если непонятно

- В PR комменте: «Я не уверен куда это положить — подскажите?»
- В канале команды: «кто-нибудь объяснит, зачем здесь Specification?»

Не угадывай молча. Неправильное размещение кода = недели рефакторинга позже.
