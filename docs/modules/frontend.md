# Frontend Module (Vue 3 SPA)

Customer-facing SPA, поднимается nginx'ом на `/`. Провайдерский кабинет
(`/o/:slug/*`) и личный кабинет клиента (`/dashboard`) живут в том же
приложении.

## Стек
- Vue 3 (Composition API, `<script setup lang="ts">`)
- Vite 7 (dev server + production build)
- Pinia 3 (stores)
- Vue Router 4 (history mode)
- Vue i18n 9 (RU/EN messages)
- TailwindCSS 3 + design tokens через CSS custom properties (ADR-014)
- Axios (HTTP client с JWT interceptor)
- Vitest (unit) + Playwright (E2E)

## Структура
```
frontend/src/
├── api/              — типизированные API клиенты (axios)
├── modules/          — feature-модули (auth, catalog, booking, dashboard, provider, design-system)
│   └── {feature}/
│       ├── views/    — страницы, замапленные на routes
│       └── components/
├── shared/
│   ├── components/base/  — 28 переиспользуемых компонентов
│   ├── composables/  — useTheme, useToast
│   ├── i18n/         — RU/EN словари
│   └── styles/       — tokens.css + base.css
├── stores/           — Pinia stores (auth, catalog, booking, org, payouts)
├── types/            — DTO интерфейсы (auth, catalog, booking)
├── router/index.ts   — маршрутизация + guards
├── App.vue           — layout shell
└── main.ts           — bootstrap + pinia + router + i18n
```

## Routing map

### Public
- `/` → redirect на `/catalog`
- `/catalog` — каталог услуг
- `/catalog/:id` — детальная страница услуги
- `/login`, `/register`, `/register/customer`, `/register/provider`
- `/verify-email/:token`, `/verify-phone`
- `/forgot-password`, `/reset-password/:token`
- `/design-system` — витрина компонентов
- `/forbidden` — 403 page
- `/:pathMatch(.*)*` — 404 page

### Authenticated
- `/book/:serviceId` — форма бронирования (требует `auth`)
- `/bookings/:id` — подтверждение брони
- `/dashboard` — кабинет клиента (табы: bookings / profile / notifications / favorites / payment-methods)
- `/provider/onboarding` — 4-step wizard создания организации

### Provider (`/o/:slug/*`)
Layout `OrgLayout` с sidebar. Каждый route требует конкретный
`MembershipPermission` (проверка client-side UX gate + backend middleware):
- `/o/:slug` → Dashboard (analytics.view)
- `/o/:slug/services` → Services list (services.edit)
- `/o/:slug/services/new` → Create (services.create)
- `/o/:slug/services/:id/edit` → Edit (services.edit)
- `/o/:slug/calendar` → Calendar (bookings.view)
- `/o/:slug/inbox` → Pending bookings (bookings.view)
- `/o/:slug/payouts` → Payouts (payouts.view)
- `/o/:slug/team` → Team (team.view)
- `/o/:slug/settings` → Settings (settings.view)

## Auth flow

### Login (`POST /auth/login`)
1. LoginView отправляет `{email, password}` через `authStore.login()`.
2. Backend возвращает пару токенов: `{access_token, refresh_token, expires_in, token_type}`.
3. Токены сохраняются в `localStorage` (`auth:token`, `auth:refresh`, см. ADR-015).
4. `authStore.login()` сразу делает `GET /auth/me` и `GET /me/memberships`.
5. Router redirect на `?redirect=` или `/catalog`.

### Register
- `/register` — выбор роли (customer | provider).
- `/register/customer` — минимальная форма (имя, email, пароль).
- `/register/provider` — форма + org-поля → после register
  redirect на `/provider/onboarding?org_name=...` с сохранением query.
- Оба возвращают user + токены (одноразовый 201 `/auth/register`).

### Refresh
- Axios interceptor ловит 401 → ставит запрос в очередь → `POST /auth/refresh`
  через отдельный axios instance (без interceptor, избегаем рекурсии).
- Новые токены кладутся в localStorage, исходный запрос ретраится.
- Параллельные 401-запросы ждут общий `refreshPromise` — один refresh на все.

### Logout (`POST /auth/logout`)
1. Ревокация refresh token на backend (best effort, ошибки игнорируются).
2. `clearTokens()` в localStorage + reset Pinia state.
3. Router redirect на `/login`.

### Hydrate (bootstrap)
`main.ts` вызывает `authStore.hydrate()` до mount:
- Если `auth:token` есть — `GET /auth/me` + `GET /me/memberships`.
- Если /auth/me фейлится (протухший токен) — clearTokens + redirect на /login отработает router guard.

## Permissions / RBAC (client-side)

`authStore.canAccessOrg(slug, permission)` — зеркалирует backend
`MembershipRole::PERMISSIONS`:
- `owner` — all permissions (services.*, bookings.*, payouts.*, team.*, settings.*, organization.archive).
- `admin` — services.*, bookings.*, payouts.view, team.view, settings.*.
- `staff` — services.edit, bookings.*, team.view, settings.view.
- `viewer` — bookings.view only.

Client-side gates в router guard (meta `orgPermission`) + UI (v-if на кнопках).
**Реальная авторизация всегда на backend** (`MembershipGuardMiddleware`).

## API client

`src/api/client.ts` — axios instance с baseURL `/api/v1`:
- Request interceptor: `Authorization: Bearer <access_token>`.
- Response interceptor: 401 → refresh → retry (избегает рекурсии на /auth/refresh + /auth/login).
- Типизация: все модули возвращают `Envelope<T>` (`{success, data, error, meta}`).

Типизированные модули:
- `auth.api.ts` — login/register/refresh/me/logout + memberships.
- `catalog.api.ts` — list/detail services, categories.
- `availability.api.ts` — availability queries.
- `booking.api.ts` — user bookings CRUD.
- `services.api.ts`, `org.api.ts`, `org-bookings.api.ts`, `payouts.api.ts`,
  `memberships.api.ts` — provider кабинет.

## Testing

### Unit (Vitest) — 165+ тестов
- Stores (auth, booking, org, payouts, catalog): mock API, TDD.
- Composables (useTheme, useToast).
- Базовые компоненты (BaseInput, BaseSelect, BaseStepper, etc).

Запуск: `npm run test:unit -- --run`.

### E2E (Playwright)
- `data-test-id` — основной локатор (настроено в playwright.config.ts).
- HAR или inline `page.route` моки — live backend не требуется.
- `baseURL`: nginx `:8080` для локального dev, `:4173` preview для CI.

Spec directories:
- `e2e/spec/catalog/` — browse + filter + detail.
- `e2e/spec/booking/` — TIME_SLOT + QUANTITY + dashboard.
- `e2e/spec/auth/` — login happy + 401 error.
- `e2e/spec/design-system/` — компоненты.

Запуск: `CI=true npx playwright test --project=chromium`.

## i18n

Сообщения в `src/shared/i18n/{ru,en}.ts` — плоская древовидная структура.
Импорт `useI18n` + `t('namespace.key')`.

Gotcha: символ `@` в строках парсится vue-i18n как linked message.
Email placeholder `you@example.com` обёрнут в escape: `'you{\'@\'}example.com'`.

## Related
- ADR-003: JWT for customer, session for admin.
- ADR-014: Design tokens + Tailwind bridge.
- ADR-015: JWT storage — localStorage на MVP, migration к httpOnly cookie в Plan 11.
- `.claude/rules/vue.md` — правила организации кода.
- `.claude/rules/testing.md` — стратегия тестирования.
