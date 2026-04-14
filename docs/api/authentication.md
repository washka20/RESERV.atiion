# Authentication

Dual-auth система: **JWT** для customer API (`/api/v1/*`), **session** для admin (`/admin/*`).

## Customer JWT Flow

1. `POST /api/v1/auth/register` → `201 { access_token, refresh_token, expires_in: 3600, token_type: "Bearer", user: {...} }`
2. Запросы к `/api/v1/*` — заголовок `Authorization: Bearer <access_token>`
3. Через ~1ч — `POST /api/v1/auth/refresh` с `{ refresh_token }` → новая пара (старый refresh помечен `revoked_at`)
4. `POST /api/v1/auth/logout` с `{ refresh_token }` → 204, refresh revoked

### Хранение на клиенте (рекомендуемо)

- **Access token** — в памяти SPA (Pinia store), НЕ в `localStorage` (XSS-риск)
- **Refresh token** — httpOnly cookie (Plan 6+) или Pinia persisted с осторожностью
- В БД backend хранится **sha256-хеш** refresh-токена, не plaintext

### Настройки (`config/jwt.php` + `.env`)

| Параметр | ENV | По умолчанию | Описание |
|----------|-----|--------------|----------|
| secret | `JWT_SECRET` | — (required) | HMAC ключ, ≥ 64 символа |
| ttl | `JWT_TTL` | 3600 | Access token TTL (seconds) |
| refresh_ttl | `JWT_REFRESH_TTL` | 2592000 | Refresh TTL — 30 дней |
| issuer | `APP_URL` | http://localhost | JWT `iss` claim |
| audience | — | `reservatiion-customer` | JWT `aud` claim |
| algorithm | — | HS256 | Подпись |

## Admin Session Flow (Plan 5)

- Filament встроенный login на `/admin/login`
- Laravel session cookie + CSRF
- Guard `web` (default)
- Доступ только ролям `admin` / `manager` (enforced в Plan 5 через Policy + Filament canAccess)

## Guards (`config/auth.php`)

| Guard | Driver | Provider | Где |
|-------|--------|----------|-----|
| `web` (default) | session | `users` (Eloquent UserModel) | `/admin/*` |
| `api` | jwt | `users` | `/api/v1/*` |

Драйвер `jwt` регистрируется в `Identity\Provider::boot()` через `Auth::extend('jwt', ...)` и возвращает `JwtGuard`.

## Envelope (единый формат ответов)

### Успех

```json
{
  "success": true,
  "data": { "...": "..." },
  "error": null,
  "meta": null
}
```

### Ошибка

```json
{
  "success": false,
  "data": null,
  "error": {
    "code": "INVALID_CREDENTIALS",
    "message": "Invalid email or password",
    "details": null
  },
  "meta": null
}
```

**Исключение:** Laravel validation errors (422) — стандартный формат через `ValidationException::render()`, ключ `errors`. Envelope-обёртка добавится в Plan 11 (hardening).

## Error codes (Identity)

| Код | HTTP | Endpoint | Когда |
|-----|------|----------|-------|
| `INVALID_CREDENTIALS` | 401 | /login | Неверный email/password |
| `INVALID_REFRESH` | 401 | /refresh | Неверный / revoked / expired refresh token |
| `NO_TOKEN` | 401 | protected | Нет `Authorization: Bearer ...` |
| `INVALID_TOKEN` | 401 | protected | Токен не прошёл валидацию |
| `UNAUTHORIZED` | 401 | /me | Request без user (edge case) |
| `NOT_FOUND` | 404 | /me | Пользователь удалён после выдачи токена |

## Rate limiting

- `/login` — 5 попыток / минута (throttle middleware, key: email)
- Остальные endpoints — без throttle (можно добавить в Plan 11)

## Безопасность

- Refresh token ротация: на каждый успешный `/refresh` старый помечается `revoked_at`, выдаётся новый
- Reuse detection: попытка использования revoked refresh token → 401, **но** не invalidates всю цепочку (упрощение Plan 4; в Plan 11 добавим token family tracking)
- Passwords: bcrypt через Laravel `Hash::make` (cost factor из `config/hashing.php`)
- CSRF: для session guard (`/admin/*`); для API не нужен (stateless JWT)

## См. также

- ADR 003 — [JWT для customer, session для admin](../adr/003-jwt-customer-session-admin.md)
- Модуль Identity — [`docs/modules/identity.md`](../modules/identity.md)
- Тесты auth flow — [`backend/tests/Feature/Api/Auth/`](../../backend/tests/Feature/Api/Auth/)
