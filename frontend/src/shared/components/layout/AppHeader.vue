<script setup lang="ts">
/**
 * Глобальный header приложения.
 *
 * Содержит workspace switcher (Personal + memberships для authenticated user),
 * навигацию, переключатель темы и — в зависимости от auth-статуса —
 * либо аватар пользователя, либо CTA кнопки "Войти" / "Регистрация".
 *
 * Avatar click → dropdown меню (Профиль / Настройки / Выход).
 */
import { computed } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { Moon, Sun } from 'lucide-vue-next'
import BaseButton from '@/shared/components/base/BaseButton.vue'
import BaseWorkspaceSwitcher from '@/shared/components/base/BaseWorkspaceSwitcher.vue'
import AvatarMenu from '@/modules/auth/components/AvatarMenu.vue'
import { useTheme } from '@/shared/composables/useTheme'
import { useAuthStore } from '@/stores/auth.store'

type WorkspaceType = 'personal' | 'organization'
interface Workspace {
  id: string
  name: string
  type: WorkspaceType
}

const { isDark, toggle } = useTheme()
const authStore = useAuthStore()
const route = useRoute()
const router = useRouter()

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

const workspaces = computed<Workspace[]>(() => {
  if (!authStore.isAuthenticated) return []
  const orgs: Workspace[] = authStore.memberships.map((m) => ({
    id: m.organizationSlug,
    name: m.organizationSlug,
    type: 'organization',
  }))
  return [
    { id: 'personal', name: 'Личный', type: 'personal' },
    ...orgs,
  ]
})

const activeWorkspaceId = computed<string>(() => {
  const slug = route.params.slug
  if (typeof slug === 'string' && authStore.memberships.some((m) => m.organizationSlug === slug)) {
    return slug
  }
  return 'personal'
})

function onWorkspaceChange(id: string): void {
  if (id === 'personal') {
    router.push({ name: 'dashboard' })
  } else {
    router.push(`/o/${id}`)
  }
}
</script>

<template>
  <header
    class="sticky top-0 z-20 flex items-center gap-3 border-b border-border bg-surface px-4 py-2 sm:px-6"
    data-test-id="app-header"
  >
    <RouterLink
      :to="{ name: 'catalog' }"
      class="text-lg font-semibold text-text hover:opacity-80"
      data-test-id="app-header-logo"
    >
      RESERV
    </RouterLink>

    <BaseWorkspaceSwitcher
      v-if="authStore.isAuthenticated && workspaces.length > 1"
      :workspaces="workspaces"
      :model-value="activeWorkspaceId"
      data-test-id="app-header-workspace-switcher"
      @update:model-value="onWorkspaceChange"
    />

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

      <AvatarMenu v-else :full-name="fullName" :initials="initials" />
    </div>
  </header>
</template>
