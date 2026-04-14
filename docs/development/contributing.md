# Contributing

## Workflow

1. Взять задачу из `docs/superpowers/plans/`
2. Создать ветку `feature/<name>` от main
3. Реализовать task-by-task по плану (TDD для Domain + Application)
4. Один коммит = один **работающий вертикальный срез**
5. PR в main

## Конвенции

- **Стиль кода:** `~/.claude/rules/php.md`, `~/.claude/rules/typescript.md`
- **Архитектура:** `.claude/rules/ddd.md`, `.claude/rules/modular-monolith.md`
- **API:** `.claude/rules/api.md`
- **Тесты:** `.claude/rules/testing.md`
- **Коммиты:** `~/.claude/rules/git.md`

## Чеклист перед коммитом

- [ ] Тесты зелёные: `make test`
- [ ] Линтеры зелёные: `make lint`
- [ ] Architecture tests зелёные
- [ ] Обновлена документация модуля (если менялась)
- [ ] Обновлён ADR (если принято новое архитектурное решение)

## Создание нового модуля

См. [docs/development/module-guide.md](module-guide.md) *(создаётся в Plan 3)*.

## Документация

- Module docs: `backend/app/Modules/<Module>/README.md`
- High-level docs: `docs/`
- ADR: `docs/adr/` (для значимых решений)
