# Design System Module

## Компоненты (28)

### Primitives
- `BaseButton` — variants primary/secondary/ghost/danger, sizes sm/md/lg, loading, icon slots
- `BaseSpinner` — sm/md/lg
- `BaseInput`, `BaseTextarea` — label/error/helper, prefix/suffix slots
- `BaseSelect` — native wrapper, options prop
- `BaseCheckbox`, `BaseRadio`, `BaseToggle` — v-model boolean
- `BaseCard` — padding/elevation variants
- `BaseBadge` — neutral/success/warning/danger/info
- `BaseChip` — removable, clickable, keyboard Del
- `BaseTabs` — horizontal, Arrow keys
- `BasePagination` — with ellipsis
- `BaseModal` — teleport, focus trap, Escape close
- `BaseDialog` — confirm/cancel preset over Modal
- `BaseAvatar` — fallback initials, round/square
- `BaseEmptyState` — icon + title + action slots
- `BaseSkeleton` — text/card/circle variants

### Advanced
- `BaseStatCard` — value + delta + trend
- `BaseStepper` — wizard, navigable optional
- `BaseDataTable` — sort, row actions, empty state
- `BaseCalendar` — month grid, keyboard grid nav
- `BaseFileUploader` — drag-n-drop + preview
- `BaseChart` — SVG wireframe placeholder
- `BaseTimeline` — vertical events
- `BaseToast` — teleport queue (useToast composable)
- `BaseWorkspaceSwitcher` — Slack-style dropdown
- `BaseBottomNav` — mobile bottom navigation

### Composables
- `useTheme` — dark mode
- `useToast` — toast queue
- `useFocusTrap` — modal focus trap

## Тесты

Vitest: 139 тестов (unit). Coverage > 70% для `src/shared/components/base/`.
Playwright: `/design-system` smoke + theme toggle.

## Использование

```vue
<script setup lang="ts">
import BaseButton from '@/shared/components/base/BaseButton.vue'
import { useToast } from '@/shared/composables/useToast'

const toast = useToast()
</script>

<template>
    <BaseButton @click="toast.success('Готово!')">Сохранить</BaseButton>
</template>
```

См. `/design-system` route для полного catalogue.
