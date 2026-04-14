# Identity Module

> Bounded Context: аутентификация, пользователи, роли

## Purpose

Identity — управляет учётными записями, ролями, авторизацией. Публикует domain events при регистрации/назначении роли/верификации email. Реализует dual-auth: JWT для customer API и session для admin (Filament) — см. `docs/api/authentication.md`.

## Aggregates / Entities

- **User** (aggregate root, `Domain/Entity/User.php`) — id, email, fullName, passwordHash, roles[], emailVerifiedAt
- **Role** (entity, `Domain/Entity/Role.php`) — id, name (admin | manager | user)

## Value Objects

- `UserId`, `RoleId` — extends `Shared/Domain/AggregateId`
- `Email` — RFC 5321 validation, lowercase normalization
- `HashedPassword` — только хеш, `fromPlaintext($plain, $hasher)` + `matches($plain, $hasher)`
- `FullName` — firstName + lastName + ?middleName, `full()` возвращает "First [Middle ]Last"
- `RoleName` — backed enum (Admin / Manager / User)

## Domain Events

**Публикуемые:**
- `UserRegistered(UserId, Email, occurredAt)` — после `User::register()`
- `UserRoleAssigned(UserId, RoleId, RoleName, occurredAt)` — после `User::assignRole()` (дедуплицируется)
- `UserEmailVerified(UserId, occurredAt)` — после `User::verifyEmail()` (идемпотентно)

**Слушаемые:** нет на текущий момент (появятся в Plan 7+).

## Specifications

Нет на текущий момент. Появятся при усложнении бизнес-правил (напр. `PasswordStrength`, `EmailIsUnique`).

## API endpoints (prefix `/api/v1/auth`)

| Метод | URL | Auth | Результат |
|-------|-----|------|-----------|
| POST | `/register` | — | 201 + user + tokens |
| POST | `/login` | — | 200 + tokens (rate limit 5/min) |
| POST | `/refresh` | — | 200 + new tokens (старый revoked) |
| POST | `/logout` | Bearer | 204 |
| GET | `/me` | Bearer | 200 + user profile |

### Envelope (успех)

```json
{
  "success": true,
  "data": { "user": { "id": "...", "email": "...", "...": "..." }, "access_token": "...", "refresh_token": "...", "expires_in": 3600, "token_type": "Bearer" },
  "error": null,
  "meta": null
}
```

### Error codes

| Код | HTTP | Когда |
|-----|------|-------|
| `INVALID_CREDENTIALS` | 401 | Неверный email/password на `/login` |
| `INVALID_REFRESH` | 401 | Неверный / revoked / expired refresh token |
| `NO_TOKEN` | 401 | Отсутствует `Authorization: Bearer ...` заголовок |
| `INVALID_TOKEN` | 401 | Токен не прошёл валидацию (signature / expired / issuer) |
| `UNAUTHORIZED` | 401 | `/me` без user в request |

## Filament Resources

Нет. Будет `UserResource` для admin в Plan 5.

## Dependencies

**На другие модули:** нет (Identity — корневой BC без зависимостей).

**От других модулей ожидается (события):** модули Catalog/Booking/Payment могут слушать `UserRegistered` для приветственных действий. Интеграция — в их планах.

**Shared kernel:**
- `Shared/Domain/AggregateRoot`, `AggregateId`, `DomainEvent`, `Exception/DomainException`
- `Shared/Application/Event/DomainEventDispatcherInterface`
- `Shared/Infrastructure/Event/LaravelDomainEventDispatcher`

## Тесты

- Unit: `backend/tests/Unit/Modules/Identity/` — Domain VO + Entities + Application handlers (Mockery для репозиториев)
- Feature: `backend/tests/Feature/Api/Auth/` — 25 тестов, полный HTTP flow через JSON + rate limit + envelope
- Feature: `backend/tests/Feature/Modules/Identity/Infrastructure/JwtTokenServiceTest.php` — 7 тестов (issue/refresh/revoke/parseAccess)
