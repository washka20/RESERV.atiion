<script setup lang="ts">
/**
 * Глобальный header приложения.
 *
 * Содержит workspace switcher (placeholder пока до Plan 14), навигацию по
 * основным разделам, переключатель темы и аватар пользователя (fallback на
 * инициалы). На мобильных — только логотип + theme toggle + avatar.
 */
import { ref } from 'vue'
import { RouterLink } from 'vue-router'
import { Moon, Sun } from 'lucide-vue-next'
import BaseAvatar from '@/shared/components/base/BaseAvatar.vue'
import BaseWorkspaceSwitcher from '@/shared/components/base/BaseWorkspaceSwitcher.vue'
import { useTheme } from '@/shared/composables/useTheme'

const { isDark, toggle } = useTheme()

/** Плейсхолдер до Plan 14 — реальные organizations появятся в Identity BC. */
const workspaces = [
  { id: 'personal', name: 'Личный', type: 'personal' as const },
]
const activeWorkspaceId = ref<string>('personal')
</script>

<template>
  <header
    class="sticky top-0 z-20 flex items-center gap-3 border-b border-border bg-surface px-4 py-2 sm:px-6"
    data-test-id="app-header"
  >
    <BaseWorkspaceSwitcher
      v-model="activeWorkspaceId"
      :workspaces="workspaces"
    />

    <RouterLink
      :to="{ name: 'catalog' }"
      class="ml-2 hidden text-lg font-semibold text-text hover:opacity-80 sm:inline-flex"
      data-test-id="app-header-logo"
    >
      RESERV
    </RouterLink>

    <nav class="hidden flex-1 items-center gap-4 md:flex" aria-label="Main navigation">
      <RouterLink
        :to="{ name: 'catalog' }"
        class="text-sm font-medium text-text-subtle hover:text-text"
        active-class="text-accent"
        data-test-id="app-header-nav-catalog"
      >
        Каталог
      </RouterLink>
      <RouterLink
        :to="{ name: 'dashboard' }"
        class="text-sm font-medium text-text-subtle hover:text-text"
        active-class="text-accent"
        data-test-id="app-header-nav-dashboard"
      >
        Мои брони
      </RouterLink>
      <RouterLink
        :to="{ name: 'design-system' }"
        class="text-sm font-medium text-text-subtle hover:text-text"
        active-class="text-accent"
        data-test-id="app-header-nav-design"
      >
        Design
      </RouterLink>
    </nav>

    <div class="ml-auto flex items-center gap-2">
      <button
        type="button"
        class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-border bg-surface text-text-subtle hover:text-text focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
        :aria-label="isDark ? 'Включить светлую тему' : 'Включить тёмную тему'"
        data-test-id="app-header-theme-toggle"
        @click="toggle"
      >
        <Sun v-if="isDark" class="h-4 w-4" aria-hidden="true" />
        <Moon v-else class="h-4 w-4" aria-hidden="true" />
      </button>

      <BaseAvatar alt="User" fallback="U" size="sm" data-test-id="app-header-avatar" />
    </div>
  </header>
</template>
