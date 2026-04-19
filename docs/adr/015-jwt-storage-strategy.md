# ADR-015: JWT storage strategy — localStorage на MVP

## Status
Accepted (2026-04-19)

## Context
Frontend SPA (Vue 3) аутентифицируется на backend-API через короткоживущий
access token + долгоживущий refresh token (JWT пара, см. ADR-003). Нужно
решить, где хранить токены в браузере так, чтобы:

1. axios-interceptor мог подставить `Authorization: Bearer <at>` на каждый
   запрос без user-interaction;
2. пара переживала page refresh (иначе user должен был бы логиниться после
   каждого F5);
3. refresh flow (401 → `/auth/refresh` → retry) работал автономно.

Варианты хранения:
- **localStorage** — доступно синхронно, переживает refresh, но уязвимо к
  XSS (любой JS на домене может украсть).
- **httpOnly cookie** — неуязвимо к XSS-read, но требует CSRF-защиту и
  backend-support (SameSite, Secure, Path).
- **In-memory (Pinia state)** — полностью неуязвимо к XSS-read, но теряется
  при refresh → UX regression.

## Decision
На MVP оба токена (access + refresh) хранятся в `localStorage` под ключами
`auth:token` и `auth:refresh` (см. `frontend/src/api/client.ts`).

Принято осознанно: XSS exposure — известный trade-off, компенсируется:
1. строгим CSP (`unsafe-inline` запрещён для скриптов, см. nginx conf);
2. отсутствием сторонних 3rd-party скриптов в production bundle;
3. Vue template sanitization (`v-html` не используется, см. global rules);
4. коротким TTL access token (15 мин) — украденный токен быстро истекает;
5. refresh token может быть revoked через `/auth/logout` на backend.

## Consequences
+ Простая SPA-интеграция — interceptor в client.ts читает/пишет синхронно.
+ Переживает refresh страницы — `hydrate()` в main.ts подтягивает токен и
  вызывает `/auth/me`.
+ Не требует изменений backend-auth: JWT bearer flow уже реализован
  (Plan 10 Identity).
− XSS даёт прямой доступ к refresh token → злоумышленник может
  поддерживать сессию неограниченно долго. Митигация в Plan 11 hardening
  (см. ниже).
− Нет automatic logout при закрытии вкладки — localStorage persistent.
− CSRF не нужен, т.к. токены не идут автоматом через cookie — это плюс в
  нашей схеме (не добавляет сложности), но и не защита от XSS.

## Alternatives

### 1. httpOnly cookie (refresh) + localStorage (access)
Refresh в cookie невозможно стащить через XSS, access — короткоживущий.
Backend должен выставлять `Set-Cookie: SameSite=Strict; HttpOnly; Secure;
Path=/api/v1/auth/refresh`. Требует CSRF-защиту на state-changing endpoints
(double-submit token или SameSite=Strict). Перенесено в **Plan 11**:
hardening auth + KYC + verification.

### 2. Fully in-memory (Pinia)
После refresh — logout. Неприемлемо для MVP UX (клиенты тестируют бронь,
refresh страницы — выход).

### 3. sessionStorage
Теряется при закрытии вкладки — аналог in-memory, но переживает refresh.
Промежуточный вариант, но XSS-exposure тот же что у localStorage, без
преимуществ long-lived session.

## Migration path (Plan 11)

1. Backend добавляет endpoint `/auth/refresh` с альтернативой в cookie —
   `Set-Cookie: reserv_refresh=<rt>; HttpOnly; SameSite=Lax; Secure`.
2. Frontend перестаёт писать refresh в localStorage; access — остаётся
   (короткий TTL).
3. axios-interceptor вызывает `/auth/refresh` без передачи refresh в body
   (cookie подхватывается автоматом браузером).
4. CSRF double-submit token на `/auth/login`, `/auth/refresh`, `/auth/logout`.
5. Feature-flag для gradual rollout (cookie flow поверх localStorage flow,
   переключается через env `VITE_AUTH_REFRESH_MODE=cookie`).

## Related
- `frontend/src/api/client.ts` — setTokens/getTokens + 401 interceptor.
- `frontend/src/stores/auth.store.ts` — hydrate из localStorage на старте.
- ADR-003 — JWT для customer, session для admin.
- Plan 11 — auth hardening (KYC, verification, cookie refresh).
