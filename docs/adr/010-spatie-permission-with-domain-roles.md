# ADR 010: Spatie Permission с доменными ролями

**Status:** Accepted
**Date:** 2026-04-15

## Context

Admin-панели (Filament) нужна ролевая авторизация: policy checks, middleware, `hasRole()`, `can()`. Варианты:

1. Написать RBAC с нуля поверх доменных `Identity\Role` + `role_user`
2. `spatie/laravel-permission` — de-facto стандарт для Laravel RBAC (multi-guard, cache, активный maintenance)
3. Отказаться от policy на уровне фреймворка, проверять роли только в доменных Specification

Filament ожидает `hasRole()` на User-модели и интегрируется с Spatie из коробки. Собственная реализация — много инфраструктурной работы без доменной выгоды.

## Decision

1. **Source of truth** — доменная сущность `Identity\Role` (enum `RoleName`: `admin | manager | user`). Любое назначение/отзыв роли идёт через `AssignRoleCommand` / `RevokeRoleCommand` → `User` aggregate → таблицы `roles` + `role_user`.
2. **Spatie — read-side mirror**, используется только Filament для `UserModel::canAccessPanel()`, `hasRole()`, policy-методов `canViewAny`/`canEdit`/`canCreate`/`canDelete`. Spatie-таблицы с префиксом `spatie_*`, чтобы не конфликтовать с доменной `roles`.
3. **Синхронизация** — Laravel event listeners:
   - `SyncSpatieRoleOnUserRoleAssigned` слушает доменный `UserRoleAssigned` → `UserModel::assignRole($roleName->value)`
   - `SyncSpatieRoleOnUserRoleRevoked` слушает `UserRoleRevoked` → `UserModel::removeRole(...)`
   - Ловят `Spatie\Permission\Exceptions\RoleDoesNotExist` → логируют и продолжают (не рушат бизнес-флоу).
4. **UUID-совместимость** — `spatie_model_has_roles.model_id` — `uuid`, наш user PK тоже UUID.

## Consequences

**Плюсы:**
- Domain layer чистый — 0 импортов Spatie в `Identity/Domain/`
- Filament UI работает из коробки (policy middleware, `hasAnyRole`)
- Spatie решает cache-инвалидацию ролей

**Минусы / компромиссы:**
- **Eventual consistency** — листенер выполняется синхронно внутри запроса (после коммита транзакции через `LaravelDomainEventDispatcher`). Если листенер упал — Spatie отстаёт. Mitigation: reconciliation-команда `php artisan spatie:resync` (будет добавлена когда потребуется), + ошибки логируются.
- **Double-write** — одна лишняя INSERT в `spatie_model_has_roles` на изменение роли. Pренебрежимо.
- **Naming drift** — доменный `RoleName::User` vs Spatie роль `customer`. В SpatieRoleSeeder создаём `admin`, `manager`, `customer`; при assign доменной `User` листенер ищет Spatie-роль `user` и логирует предупреждение — осознанный компромисс (customer API пока не использует Spatie). Если появится трение — отдельный ADR на унификацию.

## Alternatives considered

- **Переписать Spatie API поверх доменных ролей** — отвергнут: дублирует то, что Spatie уже решает (cache, teams, wildcards, multi-guard). Месяцы работы ради чистоты.
- **Отказ от Spatie, собственный `hasRole` на UserModel через join с доменным `role_user`** — отвергнут: теряем policy middleware Filament, нужно реализовать cache ролей, переписать seeders. Много инфраструктуры без доменной выгоды.

## Links

- [ADR-002: Filament для admin](002-filament-for-admin.md)
- [patterns/filament-ddd-bridge.md](../patterns/filament-ddd-bridge.md)
- Plan 5 — Filament Admin Setup (`docs/superpowers/plans/2026-04-14-05-filament-admin-setup.md`)
- Listeners: `backend/app/Modules/Identity/Interface/Filament/Listener/SyncSpatieRoleOn*.php`
