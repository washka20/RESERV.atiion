# Branch Protection Rules — `main`

Required status checks, которые должны быть зелёными до merge в `main`.

## Stage 1 — Static + Audit

- `PHP extensions drift check`
- `Backend lint (Pint)`
- `Backend static analysis (PHPStan)`
- `Backend architecture (Pest arch)`
- `Frontend lint (ESLint)`
- `Backend composer audit`
- `Frontend npm audit`
- `Dependency Review`

## Stage 2 — Build

- `Docker dev image (smoke build)`
- `Docker prod image (smoke build)`
- `Docker prod image scan (Trivy)`
- `Frontend build (Vite)`
- `Backend build (prod composer smoke)`

## Stage 3 — Test

- `Backend tests (Unit + coverage)`
- `Backend tests (Feature + PG + Redis)`
- `Backend seeders smoke`
- `Frontend tests (Vitest)`

## Stage 4 — Integration (post-merge)

- `Playwright E2E (chromium, HAR моки)` — запускается на push `main` как post-merge gate. **Не required** — иначе каждый PR будет ждать ~3 мин.

## Commits

- `Commitlint (conventional commits)` — required на PR.

## Settings (`repository → settings → branches → main`)

- Require a pull request before merging: **on**
- Required approvals: 1 (для solo dev — 0)
- Dismiss stale pull request approvals when new commits are pushed: **on**
- Require status checks to pass before merging: **on**
- Require branches to be up to date before merging: **off** (с cancel-in-progress это даст лишние re-runs)
- Require conversation resolution before merging: **on**
- Require signed commits: **off** (nice-to-have)
- Require linear history: **off**
- Allow force pushes: **NEVER**
- Allow deletions: **NEVER**
- Do not allow bypassing the above settings (включая administrators): **off** для сохранения возможности emergency hotfix.

## Проверка что правила применены

```bash
gh api /repos/washka20/RESERV.atiion/branches/main/protection | jq '.'
```

Ожидается `required_status_checks.contexts` включает все чеки из Stage 1-3.
