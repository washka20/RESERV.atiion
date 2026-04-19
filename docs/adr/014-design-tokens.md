# ADR-014: Design tokens через CSS custom properties + Tailwind bridge

## Status
Accepted (2026-04-19)

## Context
Дизайн-бриф v2 (Claude Design) поставляет tokens.css с oklch цветовой моделью,
light/dark режимами и радиусами/spacing/typography. Нужно интегрировать с
существующим Tailwind stack без переписывания utility-классов в проекте.

## Decision
- **CSS custom properties** в `:root` + `:root.dark` (класс-based dark mode)
- **Tailwind theme.extend.colors** указывает на `var(--color-*)` → `bg-surface` компилируется в `background-color: var(--color-surface)`
- **useTheme composable** управляет `<html>.dark` + `localStorage['reserv:theme']`, учитывает `prefers-color-scheme` через `auto` режим

## Consequences
+ Runtime switch темы без перестройки JS
+ OKLCH perceptually uniform → переходы между оттенками естественнее
+ Tailwind utilities продолжают работать без переписывания
+ Легко добавлять бренды/темы (новый `.dark` селектор)
- OKLCH поддержка только в современных browser'ах (Safari 15.4+, Firefox 113+, Chrome 111+). Для legacy — fallback не нужен в нашем target'е.
- Переменные `var(--...)` — чуть hardh debug в DevTools.

## Alternatives
1. **SCSS variables** — отвергнуто, compile-time, нельзя runtime switch.
2. **CSS-in-JS (Emotion/Stitches)** — overhead, не нужен в Vue.
3. **Prefers-color-scheme only** — нет user override, неудобно.

## Related
- `frontend/src/shared/styles/tokens.css`
- `frontend/src/shared/composables/useTheme.ts`
- `frontend/tailwind.config.js`
- `docs/patterns/design-system.md`
