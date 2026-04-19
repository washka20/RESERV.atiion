# Identity Module

> Bounded Context: аутентификация, пользователи, роли, **организации и их членство**

## Purpose

Identity — корневой BC. Управляет:

- Учётными записями (User) и platform-ролями (admin / manager / customer).
- **Организациями** (Organization) — marketplace-провайдерами услуг.
- **Членством** (Membership) — связью User ↔ Organization с organization-level RBAC (owner / admin / staff / viewer).
- JWT для customer API и session для admin (Filament) — см. `docs/api/authentication.md`.

Публикует domain events на изменения любой из сущностей (user-, organization-, membership-level).
Identity не зависит ни от одного другого модуля. Catalog ↔ Identity интеграция через `services.organization_id` FK (см. Plan 12 Task 15).

## Aggregates / Entities

- **User** (`Domain/Entity/User.php`) — id, email, fullName, passwordHash, roles[], emailVerifiedAt.
- **Role** (`Domain/Entity/Role.php`) — platform-role (admin | manager | customer | user).
- **Organization** (`Domain/Entity/Organization.php`) — id, slug, localized name/description, type, city/district, contacts, verified, cancellationPolicy, rating/reviewsCount, archivedAt.
- **Membership** (`Domain/Entity/Membership.php`) — id, userId, organizationId, role (MembershipRole), invitedBy?, acceptedAt.

## Value Objects

- `UserId`, `RoleId`, `OrganizationId`, `MembershipId` — extends `Shared/Domain/AggregateId`.
- `Email` — RFC 5321 validation, lowercase normalization.
- `HashedPassword` — только хеш, `fromPlaintext($plain, $hasher)` + `matches($plain, $hasher)`.
- `FullName` — firstName + lastName + ?middleName, `full()` возвращает "First [Middle ]Last".
- `RoleName` — backed enum (Admin / Manager / Customer / User) — platform role.
- `OrganizationSlug` — URL-safe идентификатор, валидируется по `^[a-z0-9]+(-[a-z0-9]+)*$`.
- `OrganizationType` — backed enum (salon / rental / consult / other).
- `MembershipRole` — backed enum (owner / admin / staff / viewer) с матрицей permissions (см. ниже).
- `CancellationPolicy` — backed enum (flexible / moderate / strict).

## Organization state machine

```
[created] --verify()--> [verified]
    |                       |
    +------archive()--------+
                 |
                 v
          [archived] (terminal)
```

- `create()` — factory, emit `OrganizationCreated`, verified = false, archivedAt = null.
- `verify()` — idempotent, emit `OrganizationVerified` только на первом переходе false → true.
- `archive()` — idempotent, emit `OrganizationArchived` только на первом archivedAt = null → now.
- `update*()` (city, phone, email и т.д.) — валидация + emit `OrganizationUpdated`.

## Membership roles / permissions matrix

`MembershipRole` (organization-level) **отдельная** ось от `RoleName` (platform-level).

| Permission | Owner | Admin | Staff | Viewer |
|-----------|:----:|:----:|:----:|:----:|
| `services.create` | ✓ | ✓ | | |
| `services.edit` | ✓ | ✓ | ✓ | |
| `services.delete` | ✓ | ✓ | | |
| `bookings.confirm` | ✓ | ✓ | ✓ | |
| `bookings.cancel` | ✓ | ✓ | ✓ | |
| `bookings.view` | ✓ | ✓ | ✓ | ✓ |
| `payouts.view` | ✓ | ✓ | | |
| `team.view` | ✓ | ✓ | ✓ | |
| `team.manage` | ✓ | | | |
| `settings.view` | ✓ | ✓ | ✓ | |
| `settings.manage` | ✓ | ✓ | | |
| `organization.archive` | ✓ | | | |

Источник правды: `enum MembershipRole::PERMISSIONS`. Middleware `MembershipGuardMiddleware` использует `can(string $permission)` для route-level gate.

**Last-owner invariant.** Organization должна иметь **как минимум одного owner'а**. Revoke последнего owner'а или change-role последнего owner'а → `MembershipLastOwnerException` (HTTP 409).

## Domain Events

### Публикуемые (User)
- `UserRegistered(UserId, Email, occurredAt)` — после `User::register()`.
- `UserRoleAssigned(UserId, RoleId, RoleName, occurredAt)` — после `User::assignRole()` (дедуплицируется).
- `UserRoleRevoked(UserId, RoleId, RoleName, occurredAt)` — после `User::revokeRole()`.
- `UserEmailVerified(UserId, occurredAt)` — после `User::verifyEmail()`.

### Публикуемые (Organization / Membership)
- `OrganizationCreated(OrganizationId, OrganizationSlug, occurredAt)`.
- `OrganizationVerified(OrganizationId, occurredAt)`.
- `OrganizationArchived(OrganizationId, occurredAt)`.
- `OrganizationUpdated(OrganizationId, changedFields[], occurredAt)`.
- `MembershipGranted(MembershipId, UserId, OrganizationId, MembershipRole, occurredAt)`.
- `MembershipRevoked(MembershipId, UserId, OrganizationId, occurredAt)`.
- `MembershipRoleChanged(MembershipId, UserId, OrganizationId, oldRole, newRole, occurredAt)`.

### Слушаемые
- Filament Listeners sync `UserRoleAssigned` / `UserRoleRevoked` → Spatie Permission (см. ADR-010).

## JWT claims (memberships)

Access-токен (HS256) включает `memberships` claim — актуальный снимок organization context пользователя:

```json
{
  "sub": "user-uuid",
  "memberships": [
    { "org_id": "uuid", "org_slug": "salon-savvin", "role": "owner" },
    { "org_id": "uuid", "org_slug": "loft-23", "role": "staff" }
  ],
  "iat": 1234567890,
  "exp": 1234571490
}
```

Заполняется `AuthService::issueWithMemberships` на каждом `login` и `refresh` через `UserMembershipsLookupInterface` (под капотом — `ListUserMembershipsHandler`). Revoked membership **исчезает** из claims на следующем refresh — в access-токене (1h TTL) остаётся до expiry, но authorization проверяется на стороне API middleware через БД (MembershipGuardMiddleware не доверяет claim-у, fallback-читает membership из PG).

## API endpoints

Customer API — префикс `/api/v1/`, auth = `Bearer <JWT>`.

### Auth (prefix `/auth`)
| Метод | URL | Результат |
|-------|-----|-----------|
| POST | `/register` | 201 + user + tokens |
| POST | `/login` | 200 + tokens (rate limit 5/min) |
| POST | `/refresh` | 200 + new tokens (старый revoked) |
| POST | `/logout` | 204 |
| GET | `/me` | 200 + user profile |

### Me (prefix `/me`)
| Метод | URL | Auth | Результат |
|-------|-----|------|-----------|
| GET | `/memberships` | Bearer | 200 + `[{ membership_id, organization_id, organization_slug, role }]` |

### Organizations (prefix `/organizations`)
| Метод | URL | Auth | Permission | Результат |
|-------|-----|------|-----------|-----------|
| POST | `/` | Bearer | — | 201 + org; actor автоматически получает owner membership |
| GET | `/{slug}` | Bearer | — | 200 + public profile |
| PATCH | `/{slug}` | Bearer | `settings.manage` (owner / admin) | 200 + updated profile |
| DELETE | `/{slug}` | Bearer | `organization.archive` (owner) | 204 + archived |

### Members (prefix `/organizations/{slug}/members`)
| Метод | URL | Permission | Результат |
|-------|-----|-----------|-----------|
| GET | `/` | `team.view` | 200 + `[{ membership_id, role, user }]` |
| POST | `/invite` | `team.manage` (owner) | 201 + membership (404 если email unknown, 409 если уже member) |
| DELETE | `/{membership_id}` | `team.manage` (owner) | 204 (409 если revoking last owner) |
| PATCH | `/{membership_id}/role` | `team.manage` (owner) | 200 + updated (409 если понижение last owner) |

### Error codes
| Код | HTTP | Когда |
|-----|------|-------|
| `INVALID_CREDENTIALS` | 401 | Неверный email/password на `/login` |
| `INVALID_REFRESH` | 401 | Неверный / revoked / expired refresh token |
| `NO_TOKEN` | 401 | Отсутствует `Authorization: Bearer ...` |
| `INVALID_TOKEN` | 401 | Signature / expired / issuer mismatch |
| `ORG_NOT_FOUND` | 404 | Organization по slug/id не найдена |
| `USER_NOT_FOUND` | 404 | Invite по email к несуществующему user'у |
| `MEMBERSHIP_ALREADY_EXISTS` | 409 | Invite user'а, уже являющегося member |
| `MEMBERSHIP_LAST_OWNER` | 409 | Revoke / role-change последнего owner'а |
| `FORBIDDEN_NOT_MEMBER` | 403 | Действие требует membership, user не состоит в org |
| `FORBIDDEN_INSUFFICIENT_ROLE` | 403 | Membership есть, но permission недостаточно |

## Filament Resources

Всё читаемо admin/manager'ами (Spatie platform-role `admin` / `manager`).

- `UserResource` (+ Pages) — CRUD пользователей; AssignRole/RevokeRole actions.
- `OrganizationResource` (+ ListOrganizations, ViewOrganization) — **read-only list + view**; header actions Verify + Archive.
  - Create/edit/delete disabled: write — через public API.
  - Verify → `VerifyOrganizationCommand` (KYC, idempotent).
  - Archive → `AdminArchiveOrganizationCommand` (platform-level force-archive, минует owner-gate `ArchiveOrganizationCommand`'а).
- `MembershipResource` (+ ListMemberships) — fully read-only; фильтры по organization и role.

Sync Identity ↔ Spatie — `SyncSpatieRoleOn*` listener'ы (platform roles, не organization roles).

## Dependencies

- **На другие модули:** нет. Identity — корневой BC.
- **От Identity зависит:** Catalog.Services (`services.organization_id` FK — Plan 12 Task 15).
- **Shared kernel:** `AggregateRoot`, `AggregateId`, `DomainEvent`, `Exception/DomainException`, `Bus/CommandBusInterface`, `Event/DomainEventDispatcherInterface`, `Transaction/TransactionManagerInterface`.

## Тесты

- **Unit:** `backend/tests/Unit/Modules/Identity/` — все VO, Organization / Membership invariants, Application handlers (mockery).
- **Feature API:**
  - `backend/tests/Feature/Api/Auth/` — полный auth flow + JWT claims memberships.
  - `backend/tests/Feature/Api/Identity/` — Organization / Membership / Me Memberships API.
- **Feature Admin:** `backend/tests/Feature/Admin/OrganizationAdminTest.php` + `UserResourceAccessTest.php` + `AuthorizationTest.php`.
- **Architecture:** Identity.Domain не использует Laravel; Identity.Application не использует Eloquent.

Детальная документация: [`docs/modules/identity.md`](../../../../docs/modules/identity.md). ADR: [`docs/adr/012-organizations-memberships.md`](../../../../docs/adr/012-organizations-memberships.md).
