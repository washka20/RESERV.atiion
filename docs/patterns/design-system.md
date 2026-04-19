# Design System Pattern

## Overview

RESERV.atiion использует custom design system (не UI-kit) на CSS custom properties + Vue 3 SFC компонентах.

## Структура

- `frontend/src/shared/styles/tokens.css` — tokens
- `frontend/src/shared/styles/reset.css` — reset + accessibility defaults
- `frontend/src/shared/styles/base.css` — entry (imports tokens + reset + @tailwind)
- `frontend/src/shared/components/base/` — Base компоненты (PascalCase `BaseX.vue`)
- `frontend/src/shared/composables/` — useTheme, useToast, useFocusTrap
- `frontend/src/shared/i18n/` — Vue I18n 9 словари
- `frontend/src/modules/design-system/DesignSystemView.vue` — `/design-system` catalogue

## Naming

- Components: `BaseX.vue` (PascalCase)
- Composables: `useX.ts`
- CSS: kebab-case
- data-test-id: `base-{component}` / `base-{component}-{variant}` / `{feature}-{element}-{type}`

## Conventions

- `<script setup lang="ts">`
- `defineProps<T>()` + `withDefaults`
- `defineEmits<T>()` с strict types
- TypeScript strict (включая noUncheckedIndexedAccess)
- Accessibility: aria-label, role, keyboard nav, focus trap для Modal
- Prefers-reduced-motion уважать
- Vitest для каждого компонента (smoke + interactive)

## Dark mode

Через `useTheme()` composable:
- `theme.value: 'light' | 'dark' | 'auto'` persisted в localStorage
- `auto` = prefers-color-scheme
- `isDark.value` computed, toggle `<html>.dark` класс

## i18n

Vue I18n 9, locale `ru`, fallback `en`. Словари в `frontend/src/shared/i18n/{ru,en}.ts`. Scope: `common.*`, `auth.*`, `catalog.*`, `booking.*`, `profile.*`.

## Design system page

`/design-system` — visual catalogue всех Base компонентов в light + dark. Используется для ручной проверки и как contract reference.
