<script setup lang="ts">
/**
 * Глобальный header приложения.
 *
 * Содержит workspace switcher (placeholder пока до Plan 14), навигацию по
 * основным разделам, переключатель темы и — в зависимости от auth-статуса —
 * либо аватар пользователя (для authenticated), либо CTA кнопки "Войти" /
 * "Регистрация" (для guest). На мобильных — только логотип + theme toggle +
 * auth CTA / avatar.
 */
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { Moon, Sun } from 'lucide-vue-next'
import BaseAvatar from '@/shared/components/base/BaseAvatar.vue'
import BaseButton from '@/shared/components/base/BaseButton.vue'
import { useTheme } from '@/shared/composables/useTheme'
import { useAuthStore } from '@/stores/auth.store'

const { isDark, toggle } = useTheme()
const authStore = useAuthStore()

const initials = computed<string>(() => {
  const u = authStore.user
  if (!u) return 'U'
  const f = u.firstName?.[0] ?? ''
  const l = u.lastName?.[0] ?? ''
  const combined = `${f}${l}`.trim()
  return combined || (u.email?.[0]?.toUpperCase() ?? 'U')
})

const fullName = computed<string>(() => {
  const u = authStore.user
  if (!u) return 'User'
  return `${u.firstName} ${u.lastName}`.trim() || u.email
})
</script>

<template>
  <header
    class="sticky top-0 z-20 flex items-center gap-3 border-b border-border bg-surface px-4 py-2 sm:px-6"
    data-test-id="app-header"
  >
    <!-- WorkspaceSwitcher спрятан до Plan 14 (Orgs во frontend) — пока только
         один workspace Personal, переключать нечего. -->
    <RouterLink
      :to="{ name: 'catalog' }"
      class="text-lg font-semibold text-text hover:opacity-80"
      data-test-id="app-header-logo"
    >
      RESERV
    </RouterLink>

    <nav class="hidden flex-1 items-center gap-4 md:flex" aria-label="Main navigation">
      <RouterLink
        :to="{ name: 'catalog' }"
        class="text-sm font-medium text-text-subtle hover:text-text"
        active-class="!text-accent !font-semibold"
        data-test-id="app-header-nav-catalog"
      >
        Каталог
      </RouterLink>
      <RouterLink
        :to="{ name: 'dashboard' }"
        class="text-sm font-medium text-text-subtle hover:text-text"
        active-class="!text-accent !font-semibold"
        data-test-id="app-header-nav-dashboard"
      >
        Мои брони
      </RouterLink>
      <RouterLink
        :to="{ name: 'design-system' }"
        class="text-sm font-medium text-text-subtle hover:text-text"
        active-class="!text-accent !font-semibold"
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

      <template v-if="!authStore.isAuthenticated">
        <RouterLink to="/login" data-test-id="app-header-login-link">
          <BaseButton variant="ghost" size="sm" test-id="app-header-login-btn">
            Войти
          </BaseButton>
        </RouterLink>
        <RouterLink to="/register" data-test-id="app-header-register-link">
          <BaseButton variant="primary" size="sm" test-id="app-header-register-btn">
            Регистрация
          </BaseButton>
        </RouterLink>
      </template>

      <BaseAvatar
        v-else
        :alt="fullName"
        :fallback="initials"
        size="sm"
        data-test-id="app-header-avatar"
      />
    </div>
  </header>
</template>
