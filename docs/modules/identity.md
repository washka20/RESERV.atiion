# Identity Module

Bounded context для аутентификации, пользователей и ролей. Источник правды в монолите: [`backend/app/Modules/Identity/README.md`](../../backend/app/Modules/Identity/README.md).

## Архитектура

Identity следует Clean Architecture (4 слоя) — детали в `docs/architecture/clean-architecture.md`.

```
app/Modules/Identity/
├── Domain/              # чистый PHP, 0 зависимостей от Laravel
│   ├── Entity/          # User (aggregate root), Role
│   ├── ValueObject/     # Email, HashedPassword, FullName, UserId, RoleId, RoleName
│   ├── Event/           # UserRegistered, UserRoleAssigned, UserEmailVerified
│   ├── Repository/      # UserRepositoryInterface, RoleRepositoryInterface
│   ├── Service/         # PasswordHasherInterface
│   └── Exception/       # DuplicateEmail, InvalidCredentials, InvalidEmail
├── Application/
│   ├── Command/         # RegisterUser, AssignRole, VerifyEmail (CQRS write)
│   ├── Query/           # GetUserProfile, ListUsers (CQRS read через DB::table)
│   ├── DTO/             # UserDTO
│   └── Service/         # AuthService, TokenPair, ParsedClaims, JwtTokenServiceInterface
├── Infrastructure/
│   ├── Persistence/
│   │   ├── Model/       # Eloquent: UserModel, RoleModel, RefreshTokenModel
│   │   ├── Repository/  # EloquentUserRepository, EloquentRoleRepository
│   │   └── Mapper/      # UserMapper, RoleMapper (Domain ↔ Eloquent)
│   └── Auth/            # JwtTokenService, JwtGuard, JwtUserProvider, BcryptPasswordHasher
└── Interface/
    └── Api/
        ├── Controller/   # AuthController
        ├── Request/      # RegisterRequest, LoginRequest, RefreshRequest
        ├── Resource/     # UserResource
        ├── Middleware/   # JwtAuthMiddleware
        └── routes.php
```

## Flow регистрации (sequence)

```
POST /api/v1/auth/register
  → RegisterRequest (FormRequest validation)
  → AuthController::register()
    → RegisterUserHandler::handle(RegisterUserCommand)
      → UserRepo::existsByEmail() — DuplicateEmail если занят
      → User::register() — создаёт aggregate, emit UserRegistered
      → RoleRepo::findByName(User) — default роль
      → User::assignRole(role) — emit UserRoleAssigned
      → UserRepo::save() — DB::transaction: users INSERT + role_user sync
      → DomainEventDispatcher::dispatchAll(user.pullDomainEvents())
    → AuthService::issueForUserId() → JwtTokenService::issue()
      → HS256 access token (1h TTL)
      → refresh token (64 hex, sha256 hash в refresh_tokens)
    → GetUserProfileHandler::handle() — DB::table join для UserDTO
    → UserResource::fromDTO() — envelope
  → 201 { success, data: { user, access_token, refresh_token, expires_in, token_type }, error: null, meta: null }
```

## Admin (Filament)

### Resources
- `UserResource` — CRUD пользователей. Read через `UserModel` Eloquent. Write через CommandBus (`RegisterUserCommand`, `UpdateUserCommand`).
  - Form: email, password (hashed in domain), first/last/middle name, Spatie roles (read-only — изменение через `AssignRoleAction`)
  - Table: id, email, full_name, roles (badge), email_verified_at, created_at
  - Filters: по роли, по verified status

### Actions
- `AssignRoleAction` — диспатчит `AssignRoleCommand(userId, RoleName)`.
- `RevokeRoleAction` — диспатчит `RevokeRoleCommand`.
- Обе видны только пользователям с ролью `admin`.

### Authorization
- `UserModel::canAccessPanel()` — `admin` или `manager`
- `UserResource::canViewAny` / `canEdit` — `admin` или `manager`
- `UserResource::canCreate` / `canDelete` — только `admin`
- Customer (Spatie роль `customer`) → 403 на `/admin`

### Sync Identity ↔ Spatie
- `SyncSpatieRoleOnUserRoleAssigned` слушает `UserRoleAssigned` → `$user->assignRole($roleName->value)`
- `SyncSpatieRoleOnUserRoleRevoked` слушает `UserRoleRevoked` → `$user->removeRole(...)`
- Domain `RoleName` enum (admin/manager/user) — source of truth; Spatie — mirror.
- См. [ADR-010](../adr/010-spatie-permission-with-domain-roles.md).

## Ссылки

- Код: [`backend/app/Modules/Identity/`](../../backend/app/Modules/Identity/)
- API: [`docs/api/authentication.md`](../api/authentication.md)
- ADR dual-auth: [`docs/adr/003-jwt-customer-session-admin.md`](../adr/003-jwt-customer-session-admin.md)
- Тесты: [`backend/tests/Feature/Api/Auth/`](../../backend/tests/Feature/Api/Auth/)
