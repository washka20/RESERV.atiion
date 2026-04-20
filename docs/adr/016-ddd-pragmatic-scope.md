# ADR 016: DDD pragmatic scope — упрощение после Plan 14

**Status:** Accepted
**Date:** 2026-04-20
**Supersedes in parts:** ADR-004 (Specification Pattern), ADR-007 (Read-side без Eloquent)

## Context

После 14 планов и 5 реализованных BC (Identity, Catalog, Booking, Payment, Platform) провели ретроспективу использования DDD-инфраструктуры, заложенной в Plan 3 (Modular Monolith Skeleton):

| Артефакт | Положенное под него | Реальное использование |
|---|---|---|
| `Shared/Domain/Specification/{And,Or,Not}Specification` | Композиция через `->and()/->or()/->not()` | **0 вызовов** в Application коде. Покрывалось только собственным unit-тестом. |
| `Booking/Domain/Specification/{SlotIsAvailable, QuantityIsAvailable}` | Проверки доступности | **0 импортов** вне тестов. Логика дублирована в `AvailabilityChecker` стратегиях. |
| `Catalog/Domain/Specification/{ServiceIsActive, ServiceHasSufficientInfo}` | Правила публикации услуги | **0 импортов** нигде. |
| `Domain/Service/` пустые папки | Domain services не принадлежащие entity | Пусты в Catalog, Payment — 4 папки без контента. |
| `Domain/Specification/` пустые папки | Правила модуля | Пусты в Identity, Payment — 2 папки. |
| `DB::table()` в 21 Query Handler | Защита от N+1, raw SQL для сложных джойнов | Большинство — простые `SELECT WHERE`, mapping row→DTO вручную. Реальная агрегация только в payouts и availability. |

Оценка будущих 3 планов (9 S3, 10 Landing, 11 Hardening):
- **0 новых Specification** (лёгкие модули: storage/landing/deploy)
- **0 heavy read queries** (Landing — wrappers над существующими; Hardening — cache decorator)
- **~2 VO + 1 тонкий Entity** суммарно

Из 11+ планов полный DDD-вес оправдан только в Booking (8 specs — но композиция всё равно ручная через Policy классы). Plan 3 задал шаблон сильнее, чем потребовалось.

## Decision

**Упростить DDD-инфраструктуру до реально используемой:**

1. **Specification Pattern (частично supersedes ADR-004):**
   - Удалить `AndSpecification`/`OrSpecification`/`NotSpecification` базы (0 usage).
   - Удалить методы `->and()/->or()/->not()` из `Specification` (некого композировать).
   - Оставить `Specification` как тонкий базовый класс с `isSatisfiedBy() + recordFailure() + failureReason()` — когда нужно инкапсулировать правило.
   - Использовать Specification **только** если правило:
     - Реально переиспользуется в >2 местах, ИЛИ
     - Инкапсулирует сложное условие с причиной провала для UX, ИЛИ
     - Композируется с другими через ручной if-else в Policy фасаде.
   - Иначе — метод на Entity (`Service::isActive()`, `Booking::canBeCancelled()`) или private helper в handler.

2. **Read-side (частично supersedes ADR-007):**
   - **Новое default:** Query Handler может использовать Eloquent `::with() + Resource` для простых `get by id` / `list with filters`.
   - `DB::table()` → DTO оставить только для:
     - Сложных агрегаций (payouts summary, availability calc), где Eloquent relations создают N+1.
     - Ситуаций, где профилирование показало проблему.
   - Существующие 21 handler с `DB::table()` НЕ мигрировать (нулевой ROI).
   - Писать новые handler'ы с Eloquent по умолчанию — перейти на raw только когда доказано нужно.

3. **Domain subfolders (новое правило):**
   - **Baseline для нового модуля:** `Domain/{Entity, ValueObject}` — обязательные.
   - `Domain/{Event, Exception, Repository}` — создавать когда появляется первое содержимое.
   - `Domain/{Specification, Service, Gateway, ...}` — создавать **только** когда есть первый класс, не «на вырост».
   - Пустые `Domain/*` папки с `.gitkeep` — удалить.

4. **Write-side CQRS — не трогать:**
   - Command handlers с Entity + Mapper + Repository остаются. Это реальный domain layer, даёт testability без БД.
   - Domain Events + Outbox остаются.

## Consequences

**Плюсы:**
- Plan 3 шаблон больше не врёт про сложность: новичок видит реально нужные слои, не 7 пустых папок.
- Меньше cargo-cult абстракций. Composable Specification pattern — верный инструмент при реальной композиции; без неё это просто классы-обёртки.
- Read-side Eloquent для простых queries сократит boilerplate в Plan 9/10/11 в разы.

**Минусы:**
- Часть unit-тестов на удалённые specs ушла (19 тестов из 757).
- Документация и module-guide требуют апдейта (сделано в том же PR).
- ADR-004 и ADR-007 требуют знания этого ADR для понимания.

## Что конкретно удалено

- `App\Shared\Domain\Specification\AndSpecification`
- `App\Shared\Domain\Specification\OrSpecification`
- `App\Shared\Domain\Specification\NotSpecification`
- `Specification::and()`, `::or()`, `::not()` методы
- `App\Modules\Catalog\Domain\Specification\ServiceIsActive`
- `App\Modules\Catalog\Domain\Specification\ServiceHasSufficientInfo`
- `App\Modules\Booking\Domain\Specification\SlotIsAvailable`
- `App\Modules\Booking\Domain\Specification\QuantityIsAvailable`
- Пустые папки: `Catalog/Domain/Service/`, `Identity/Domain/Specification/`, `Payment/Domain/Service/`, `Payment/Domain/Specification/`

## Что оставлено (активно используется)

- `App\Modules\Booking\Domain\Specification\BookingPolicy` + `CancellationPolicy` (фасады с реальной логикой, DI-wired)
- `WithinBookingWindow`, `UserNotExceedsLimit`, `WithinCancellationWindow`, `BookingNotAlreadyCompleted` (инкапсулируют правила, вызываются из Policy)
- Базовый `Specification` класс
- Весь CQRS write-side
- Все существующие Query handlers с `DB::table()` (не мигрируем ради миграции)

## Alternatives considered

- **Оставить как есть** — отвергнут: Plan 9 Storage модуль опять получил бы шаблонные 7 пустых папок, шаблон продолжал бы врать.
- **Полностью убрать Specification pattern, всё в Entity методы** — отвергнут: Booking Policy реально используется handler'ами через DI, миграция была бы трудозатратной без значимого выигрыша.
- **Миграция всех Query handlers на Eloquent** — отвергнут: нулевой ROI, существующий код работает, время потратилось бы на рефакторинг ради эстетики.
