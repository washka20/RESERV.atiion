# ADR 012: Organizations + Memberships (marketplace identity)

**Status:** Accepted
**Date:** 2026-04-19

## Context

RESERV.atiion — dual-sided marketplace: customer'ы бронируют услуги, provider'ы их предоставляют. На Plan 1-11 была упрощённая модель — platform-админы создавали services в одной технической организации (`platform-admin` с fixed UUID), customer'ы бронировали через customer API. Это тупиковая модель:

- Нельзя пригласить стороннего владельца салона как provider'а.
- Staff мастер, работающий в нескольких салонах, нуждался бы в N учётках.
- Нет KYC/verified флага per provider.
- Archive provider'а невозможен без сноса связанных services.

Plan 12 — марketplace-ready Identity: **User + Organization + Membership** с organization-level RBAC.

Ключевые требования:

1. **Dual-role users.** Тот же User ↔ customer (бронирует) + owner собственного салона (управляет). Одна учётка, один login.
2. **Multi-org staff.** Мастер Иван — staff в "Салон Саввин" и "Барбершоп Стрелка" одновременно.
3. **Platform-independent org RBAC.** Organization-level roles (owner / admin / staff / viewer) не зависят от platform roles (admin / manager / customer).
4. **Last-owner invariant.** Organization всегда имеет хотя бы одного owner'а.
5. **Hard platform-level archive/verify.** Platform-админ может forcefully архивировать organization без согласия owner'а (например KYC-fail).
6. **JWT-friendly.** Customer API stateless → memberships claim в токене для быстрого авторизационного контекста.

## Decision

### Модель: User + Organization + Membership (три отдельных aggregate)

- **User** (`Identity/Domain/Entity/User.php`) — учётная запись.
- **Organization** (`Identity/Domain/Entity/Organization.php`) — marketplace-провайдер. Aggregate root со своим lifecycle (unverified → verified; → archived).
- **Membership** (`Identity/Domain/Entity/Membership.php`) — связь User ↔ Organization с `MembershipRole`. **Отдельный aggregate root** — его операции (grant / revoke / changeRole) эмитят собственные события, тестируются независимо, мутируют отдельную строку БД.

### Orthogonal role axes

- **Platform role** (`RoleName`: admin / manager / customer / user) — через Spatie Permission. Gate для `/admin` Filament panel'а, platform-wide операций.
- **Organization role** (`MembershipRole`: owner / admin / staff / viewer) — scoped к конкретной organization. Permissions matrix в `enum MembershipRole::PERMISSIONS`.

Axes не пересекаются: platform-админ может быть viewer'ом в чужой org; owner'а одной org — customer в другой. User не принадлежит одному "типу".

### Membership permissions через enum + middleware

```php
MembershipRole::OWNER->can('team.manage'); // true
MembershipRole::STAFF->can('team.manage'); // false
MembershipRole::STAFF->can('bookings.confirm'); // true
```

Routes декларируют permission:

```php
Route::post('/organizations/{slug}/members/invite', [...])
    ->middleware('org.member:team.manage');
```

`MembershipGuardMiddleware` достаёт membership из БД (не из JWT claim'а — чтобы revoke применялся мгновенно для критичных операций), вызывает `can()`, возвращает 403 `FORBIDDEN_INSUFFICIENT_ROLE` / `FORBIDDEN_NOT_MEMBER`.

### JWT memberships claim — read-only hint

На каждом `login` / `refresh` `AuthService::issueWithMemberships` делает lookup `UserMembershipsLookupInterface` и кладёт в JWT:

```json
"memberships": [{ "org_id": "...", "org_slug": "salon-savvin", "role": "owner" }]
```

Claim используется для:
- `GET /me/memberships` (отдаётся прямо из parsed claim'а — fast path).
- Frontend UI (org-switcher без API-round-trip).

Claim **не** используется как source of truth для authorization на mutating endpoint'ах — там middleware читает актуальный membership из PG. Trade-off: revoked membership продолжает "работать" для read-only до следующего refresh (1h max). Для марketplace UX приемлемо.

### Last-owner invariant на уровне handler + FOR UPDATE

Invariant "организация всегда имеет owner'а" проверяется **не** в aggregate (Membership не знает про остальные membership'ы), а в `RevokeMembershipHandler` / `ChangeMembershipRoleHandler`:

```php
$this->tx->transactional(function () use ($cmd) {
    $target = $this->memberships->findByIdForUpdate($id); // SELECT ... FOR UPDATE
    if ($target->role() === OWNER) {
        $ownerCount = $this->memberships->countOwners($target->organizationId());
        if ($ownerCount <= 1) {
            throw new MembershipLastOwnerException();
        }
    }
    $this->memberships->delete($target);
});
```

Row-lock держится до commit'а транзакции. Конкурирующий revoke блокируется, затем пересчитывает `countOwners` с актуальным значением и корректно отказывает.

### Platform force-archive (admin-level)

Два разных use case'а архивации:

- **Owner archives own org** — `ArchiveOrganizationCommand($slug, $actorUserId)`. Handler проверяет `$actor.can('organization.archive')` через membership → только owner проходит. Customer API `DELETE /organizations/{slug}`.
- **Platform admin force-archives** — `AdminArchiveOrganizationCommand($organizationId)`. Handler **не** проверяет membership — authorization выполняется Filament gate'ом (`canViewAny` admin/manager). Используется только из Filament `ArchiveOrganizationAction`.

Это явное разделение устраняет dual-purpose command и делает security boundaries прозрачными в code review.

### Service.organization_id (migration)

`services.organization_id` — FK на `organizations.id`, NOT NULL. Backfill на миграции: все существующие services → `platform-admin` org (fixed UUID `00000000-0000-0000-0000-000000000001`). Новые service создаются с явным `organizationId` в CreateServiceCommand. Catalog BC не импортирует Identity — работает через UUID (publication through Shared domain events по требованию).

## Consequences

### Плюсы

- **Dual-role users работают нативно.** User может быть customer'ом, owner'ом одной org, staff другой — без join-таблиц "customer_profiles / provider_profiles".
- **Marketplace-ready модель.** Легко расширить до enterprise-org (multi-location, billing-owner отдельно от operational-owner) добавлением новых permission-ключей и membership fields.
- **Authorization централизован.** Permissions-matrix — одна константа. Добавление permission — правка enum без изменений middleware / controller'ов.
- **Independent aggregates.** Membership-изменения не дёргают Organization. Revoke staff'а — один UPDATE на `memberships`, не connect-сценарий.
- **JWT stateless + up-to-date on refresh.** Claim обновляется каждый 1h, критичные проверки фоллбэкают на PG — баланс между stateless UX и security.
- **Platform admin сохраняет полный контроль.** Force-archive через отдельную команду, никаких hacks на override owner-gate'а.

### Минусы / trade-offs

- **Orthogonal axes увеличивают concept surface.** Новому разработчику нужно понять "platform role vs membership role vs permission" — не все сразу ухватывают. Документация (этот ADR + module README) критична.
- **Revoked membership работает до 1h в JWT claim.** Mitigated фоллбэком middleware на PG, но read-only endpoints (`/me/memberships`) будут показывать stale данные до refresh. Для dashboards — ок; для security-sensitive — read mutation-gate через middleware.
- **Last-owner invariant требует FOR UPDATE.** SQLite-unfriendly, но у нас уже PG-only stack (см. ADR-011). Конкурентные revoke'и двух owner'ов сериализуются row-lock'ом.
- **Dual archive commands.** `ArchiveOrganizationCommand` + `AdminArchiveOrganizationCommand` — два похожих handler'а. Trade-off: чёткие security boundaries стоят мелкой дупликации.

### Testability

- **Domain unit:** `OrganizationTest`, `MembershipTest` — lifecycle transitions + invariant emit events.
- **Application unit:** Handler'ы с моками repos + tx → error paths без БД.
- **Feature API:** `tests/Feature/Api/Identity/` покрывают все endpoint'ы + permission-matrix.
- **Feature Admin:** `tests/Feature/Admin/OrganizationAdminTest.php` проверяет Filament gates + verify/archive actions.
- **Architecture:** Identity.Domain не импортирует Laravel; Identity.Application не использует Eloquent.

## Alternatives considered

### Spatie Teams (Jetstream-style)

Spatie Permission имеет optional teams feature — permissions scoped к team_id. Rejected потому что:

- Teams tightly coupled с User (team_id на каждой permission-assignment). Наш `Membership` — отдельный aggregate с собственным lifecycle, acceptedAt, invitedBy — это больше чем team-id.
- Domain events (`MembershipGranted`, `MembershipRoleChanged`) не мапятся на Spatie API.
- Last-owner invariant и platform force-archive требуют кастомной логики поверх Spatie — проще написать domain с нуля.
- Spatie Teams — framework-level абстракция, наша цель — domain модель, которая переживёт смену фреймворка.

Spatie Permission оставляем для **platform roles** (admin / manager / customer) — там его use case канонический.

### Single-account (Airbnb-style)

Один User, dual-role через Context-switching в UI ("View as host" / "View as guest"). Rejected:

- Не масштабируется на multi-org staff (один человек в 3 салонах).
- Organization-level permissions некуда прикрутить.
- Airbnb использует эту модель потому что у них отношения always 1:1 (user owns listings), у нас M:N (staff ↔ многие orgs).

### Separate accounts (Uber-style)

Uber разделяет customer и driver на разные аккаунты. Rejected:

- Dual-role UX ужасен (выйди из одного, войди в другой).
- В нашем контексте "customer" и "owner" могут быть одним человеком (мастер, который сам бронирует услуги другого салона). Separate accounts создаёт friction.

### ABAC (attribute-based)

Role'и заменить атрибутами (organization.is_premium, user.tier и т.д.), permissions выводить из policy-engine (OPA / Cedar). Rejected — overkill для текущего scope. MVP с enum-based permissions даёт 95% нужного. ABAC добавим когда появятся dynamic permissions (например "only services.edit if business_hours=true").

### Одно membership на user (simplified)

Запретить user'у быть members в нескольких orgs. Rejected — убивает multi-org staff use case, который выделен как ключевое требование.

## References

- Code: [`backend/app/Modules/Identity/`](../../backend/app/Modules/Identity/)
- Module deep-dive: [`docs/modules/identity.md`](../modules/identity.md)
- Plan 12 spec: `docs/superpowers/plans/2026-04-19-12-identity-extended-organizations.md`
- Related ADRs:
  - [ADR-003](003-jwt-customer-session-admin.md) — JWT vs session dual-auth
  - [ADR-010](010-spatie-permission-with-domain-roles.md) — Spatie Permission + domain roles (platform-level)
  - [ADR-011](011-booking-concurrency-strategy.md) — FOR UPDATE pattern, применяемый здесь же
- Feature tests:
  - [`backend/tests/Feature/Api/Identity/OrganizationApiTest.php`](../../backend/tests/Feature/Api/Identity/OrganizationApiTest.php)
  - [`backend/tests/Feature/Api/Identity/MembershipApiTest.php`](../../backend/tests/Feature/Api/Identity/MembershipApiTest.php)
  - [`backend/tests/Feature/Api/Identity/MeMembershipsTest.php`](../../backend/tests/Feature/Api/Identity/MeMembershipsTest.php)
  - [`backend/tests/Feature/Api/Auth/JwtClaimsTest.php`](../../backend/tests/Feature/Api/Auth/JwtClaimsTest.php)
  - [`backend/tests/Feature/Admin/OrganizationAdminTest.php`](../../backend/tests/Feature/Admin/OrganizationAdminTest.php)
