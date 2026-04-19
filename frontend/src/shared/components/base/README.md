# Base components

Design-system примитивы и advanced-компоненты. Все файлы — `BaseX.vue` PascalCase, `<script setup lang="ts">`, TypeScript strict, Vitest покрытие.

## Смотри

- `/design-system` route — live catalogue в light + dark темах
- `docs/modules/design-system.md` — карта компонентов и composables
- `docs/patterns/design-system.md` — паттерн + конвенции
- `docs/adr/014-design-tokens.md` — почему CSS custom properties + Tailwind bridge

## Composables

- `@/shared/composables/useTheme` — dark/light/auto
- `@/shared/composables/useToast` — queue toast-ов
- `@/shared/composables/useFocusTrap` — для модалок

## data-test-id

Формат: `base-{component}` или `base-{component}-{variant}`. В продуктовых view используется `{feature}-{element}-{type}`.
