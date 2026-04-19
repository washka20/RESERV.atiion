<script setup lang="ts">
import { computed } from 'vue'
import { RouterView, useRoute, useRouter } from 'vue-router'
import { Home, ListChecks, User } from 'lucide-vue-next'
import AppHeader from '@/shared/components/layout/AppHeader.vue'
import BaseBottomNav from '@/shared/components/base/BaseBottomNav.vue'
import BaseToast from '@/shared/components/base/BaseToast.vue'
import { useTheme } from '@/shared/composables/useTheme'

useTheme()

const route = useRoute()
const router = useRouter()

const NAV_ITEMS = [
  { id: 'catalog', label: 'Каталог', icon: Home, routeName: 'catalog' },
  { id: 'dashboard', label: 'Мои брони', icon: ListChecks, routeName: 'dashboard' },
  { id: 'profile', label: 'Профиль', icon: User, routeName: 'dashboard', tab: 'profile' },
]

const activeNavId = computed<string>(() => {
  if (route.name === 'dashboard') {
    return route.query.tab === 'profile' ? 'profile' : 'dashboard'
  }
  if (typeof route.name === 'string' && route.name.startsWith('catalog')) return 'catalog'
  return 'catalog'
})

function onNavChange(id: string): void {
  const item = NAV_ITEMS.find((n) => n.id === id)
  if (!item) return
  void router.push({
    name: item.routeName,
    query: item.tab ? { tab: item.tab } : undefined,
  })
}
</script>

<template>
  <div class="min-h-screen bg-bg pb-16 md:pb-0">
    <AppHeader />

    <main>
      <RouterView />
    </main>

    <BaseBottomNav
      :items="NAV_ITEMS"
      :model-value="activeNavId"
      @update:model-value="onNavChange"
    />

    <BaseToast />
  </div>
</template>
