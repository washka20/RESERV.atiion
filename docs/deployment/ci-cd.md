# CI/CD

GitHub Actions — `.github/workflows/ci.yml`.

## Триггеры

- `push` в `main`
- `pull_request` в `main`

Параллельные запуски на той же ветке отменяются (`concurrency.cancel-in-progress: true`).

## Jobs

| Job | Что делает | Зависимости |
|-----|------------|-------------|
| `backend-lint` | Composer install + Pint `--test` | — |
| `backend-test` | Pest + PostgreSQL 17 + Redis 7 service containers | — |
| `frontend-lint` | `npm ci` + `npm run lint` + `npm run type-check` | — |
| `frontend-test` | `npm ci` + Vitest (`npm run test:unit -- --run`) | — |
| `frontend-build` | `npm ci` + `npm run build`, artifact `frontend-dist` | — |

Все jobs параллельны. Merge PR блокируется, пока все не станут зелёными.

## Что добавится позже

| Job | Когда | План |
|-----|-------|------|
| `backend-phpstan` | После установки PHPStan level 8 | Plan 3 |
| `backend-arch` | После architecture tests (Pest arch API) | Plan 3 |
| `e2e` | После появления реальных страниц | Plan 4+ |
| `deploy-staging` | После hardening | Plan 11 |

## Кэши

- Composer vendor — ключ по `composer.lock`
- npm — встроенный `cache: npm` от `setup-node`

## Как добавить новый job

1. Скопировать существующий job в `ci.yml`
2. Поменять `name`, `steps`
3. Добавить в `docs/deployment/ci-cd.md` (эта таблица)
4. Закоммитить и проверить зелёный статус

## Как отлаживать падающий job

1. Открыть failed run → смотреть шаг, который покраснел
2. Локально воспроизвести команду: `cd backend && ./vendor/bin/pest` и т.д.
3. Если только в CI — проверить env, service containers, версии инструментов
4. Не отключать job — починить корневую причину

## См. также

- Spec §14
