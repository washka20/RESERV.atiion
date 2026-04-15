# ADR 002: Filament для admin panel

**Status:** Accepted
**Date:** 2026-04-14
**Revised:** 2026-04-15 — обновлён выбор версии с Filament 3 на Filament 5 (см. секцию Revision)

## Context

Admin-панель требует: CRUD для услуг/категорий/пользователей/бронирований, фильтры, сортировка, роли. Варианты:
1. Кастомный admin на Vue 3 (единый SPA с customer)
2. Filament (Laravel-native admin framework)
3. Laravel Nova (коммерческий)

## Decision

**Filament 5** (актуальный major на дату ревизии). Admin живёт на `/admin`, использует session auth Laravel (`web` guard). Отдельно от customer Vue SPA.

## Consequences

**Плюсы:**
- Forms, tables, filters, actions — из коробки, экономия месяцев работы
- Нативная интеграция с Eloquent и Spatie/Permission
- Бесплатный, активно развивается
- Schema API (v4+) даёт чёткую абстракцию UI-конфигурации, удобную для разделения на Schemas/Tables классы при росте

**Минусы:**
- Tight coupling к Laravel и Livewire 4 → не портируется в Go (но admin и не нужно портировать)
- Filament ожидает Eloquent — tension с DDD

**Разрешение tension:**
- **Filament read** (таблицы, формы отображения) — Eloquent напрямую (прагматика, admin-view)
- **Filament write** (submit формы, custom actions) — через Command Bus → Domain

## Schema architecture (Filament v4+)

Начиная с v4, Forms / Infolists / Tables базируются на едином `Filament\Schemas\Schema`. Сигнатуры методов Resource:

```php
use Filament\Schemas\Schema;
use Filament\Tables\Table;

public static function form(Schema $schema): Schema
{
    return $schema->components([
        TextInput::make('email')->email()->required(),
        // ...
    ]);
}

public static function table(Table $table): Table
{
    return $table->columns([...])->recordActions([...])->toolbarActions([...]);
}
```

**DDD мост — сигнатуры pages не меняются:**

```php
protected function handleRecordCreation(array $data): Model { /* dispatch Command */ }
protected function handleRecordUpdate(Model $record, array $data): Model { /* dispatch Command */ }
```

**Inline schema vs separated classes:**

Filament 5 рекомендует выносить Schema / Table в отдельные классы (`app/Filament/Resources/X/Schemas/XForm.php`, `Tables/XTable.php`) — *code quality tip*. Наше правило:

- **Start inline** — пока Resource умещается в один файл и в модуле один Resource.
- **Extract в Schemas/Tables классы** — когда файл Resource превышает ~200 строк или в модуле появляется 2+ Resource с общей логикой.

Решение фиксируется без лишнего рефакторинга — `app/Modules/{Module}/Interface/Filament/Resource/{Name}Resource.php` остаётся источником правды.

## Alternatives considered

- **Vue 3 admin** — отвергнут: месяцы разработки на CRUD
- **Laravel Nova** — отвергнут: коммерческий, Filament лучше по фичам
- **Filament 3 (вместо 5)** — отвергнут: v3 на закате (EOL через ~год), v4/v5 содержат Schema architecture — более чистая абстракция, стартовать с нуля на v5 cleaner, чем через год мигрировать

## Revision 2026-04-15 — от Filament 3 к Filament 5

На момент первоначального принятия ADR был зафиксирован Filament 3 — тогда это был актуальный major. К 15 апреля 2026 актуален Filament 5.5 (релиз янв. 2026, Livewire v4). v4 → v5 — breaking changes **отсутствуют** (только Livewire v4 под капотом). v3 → v4+ — значимые breaking changes:

- Forms/Tables переехали в `Filament\Schemas\Schema`
- Actions объединены в `Filament\Actions\*` (вместо `Filament\Tables\Actions\*` и `Filament\Forms\Actions\*`)
- Deferred filters по умолчанию
- Tailwind 4+ для custom theme (дефолтная тема не требует)

Поскольку на момент ревизии Plan 5 ещё не имплементирован (0 Filament-кода в repo), переход на v5 оформлен без миграционных затрат — только обновление документов и плана. Подтверждено безопасной совместимостью: PHP 8.4 ✅, Laravel 13 ✅, Spatie Permission 6.x ✅ (не зависит от Filament).
