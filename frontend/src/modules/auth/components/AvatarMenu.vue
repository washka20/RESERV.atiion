<script setup lang="ts">
/**
 * Avatar-триггер с dropdown-меню: Профиль, Настройки, Выход.
 *
 * Lightweight popover: click-outside закрывает, Escape тоже. При выборе
 * "Выход" — `authStore.logout()` + router.push('/catalog').
 */
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { LogOut, Settings, User as UserIcon } from 'lucide-vue-next'
import BaseAvatar from '@/shared/components/base/BaseAvatar.vue'
import { useAuthStore } from '@/stores/auth.store'

interface Props {
  fullName: string
  initials: string
}

defineProps<Props>()

const router = useRouter()
const authStore = useAuthStore()

const isOpen = ref<boolean>(false)
const rootRef = ref<HTMLElement | null>(null)

function toggle(): void {
  isOpen.value = !isOpen.value
}

function close(): void {
  isOpen.value = false
}

function onOutside(event: MouseEvent): void {
  if (!isOpen.value) return
  const target = event.target as Node
  if (rootRef.value && !rootRef.value.contains(target)) {
    close()
  }
}

function onKeyDown(event: KeyboardEvent): void {
  if (event.key === 'Escape') close()
}

async function goProfile(): Promise<void> {
  close()
  await router.push({ name: 'dashboard', query: { tab: 'profile' } })
}

async function goSettings(): Promise<void> {
  close()
  await router.push({ name: 'dashboard', query: { tab: 'settings' } })
}

async function onLogout(): Promise<void> {
  close()
  await authStore.logout()
  await router.push({ name: 'catalog' })
}

onMounted(() => {
  document.addEventListener('click', onOutside, true)
  document.addEventListener('keydown', onKeyDown)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', onOutside, true)
  document.removeEventListener('keydown', onKeyDown)
})
</script>

<template>
  <div
    ref="rootRef"
    class="relative inline-block"
    data-test-id="app-header-avatar-menu"
  >
    <button
      type="button"
      class="flex items-center rounded-full focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
      :aria-expanded="isOpen"
      aria-haspopup="menu"
      :aria-label="`Меню пользователя ${fullName}`"
      data-test-id="app-header-avatar"
      @click="toggle"
    >
      <BaseAvatar :alt="fullName" :fallback="initials" size="sm" />
    </button>

    <div
      v-if="isOpen"
      role="menu"
      class="absolute right-0 z-30 mt-2 min-w-[200px] overflow-hidden rounded-md border border-border bg-surface py-1 shadow-lg"
      data-test-id="app-header-avatar-menu-dropdown"
    >
      <button
        type="button"
        role="menuitem"
        class="flex w-full items-center gap-2 px-3 py-2 text-sm text-text hover:bg-surface-muted focus:outline-none focus-visible:bg-surface-muted"
        data-test-id="avatar-menu-profile"
        @click="goProfile"
      >
        <UserIcon class="h-4 w-4 text-text-subtle" aria-hidden="true" />
        <span>Профиль</span>
      </button>
      <button
        type="button"
        role="menuitem"
        class="flex w-full items-center gap-2 px-3 py-2 text-sm text-text hover:bg-surface-muted focus:outline-none focus-visible:bg-surface-muted"
        data-test-id="avatar-menu-settings"
        @click="goSettings"
      >
        <Settings class="h-4 w-4 text-text-subtle" aria-hidden="true" />
        <span>Настройки</span>
      </button>
      <div class="my-1 border-t border-border" aria-hidden="true" />
      <button
        type="button"
        role="menuitem"
        class="flex w-full items-center gap-2 px-3 py-2 text-sm text-danger hover:bg-surface-muted focus:outline-none focus-visible:bg-surface-muted"
        data-test-id="avatar-menu-logout"
        @click="onLogout"
      >
        <LogOut class="h-4 w-4" aria-hidden="true" />
        <span>Выйти</span>
      </button>
    </div>
  </div>
</template>
