<script setup lang="ts">
/**
 * Layout для provider-кабинета (/o/{slug}/*).
 *
 * Содержит sidebar с навигацией (desktop md+), mini-header с org name
 * и verified badge, main area для <RouterView />.
 *
 * Nav items фильтруются по permissions: useOrgPermission гейтит пункты
 * для ролей ниже owner/admin. Backend всё равно валидирует middleware.
 *
 * Mobile (<md): sidebar скрыт, показывается только main area и mini-header.
 * Drawer для sidebar — out of MVP scope.
 */
import { computed } from 'vue'
import { RouterView, useRoute } from 'vue-router'
import {
  Calendar,
  CheckCircle2,
  Inbox,
  LayoutDashboard,
  Package,
  Settings,
  Users,
  Wallet,
} from 'lucide-vue-next'
import type { Component } from 'vue'
import type { MembershipPermission } from '@/types/auth.types'
import { useAuthStore } from '@/stores/auth.store'

interface NavItem {
  key: string
  label: string
  icon: Component
  to: string
  permission: MembershipPermission | null
  ownerOnly?: boolean
}

const route = useRoute()
const authStore = useAuthStore()

const orgSlug = computed<string>(() => {
  const slug = route.params.slug
  return typeof slug === 'string' ? slug : ''
})

const membership = computed(() => authStore.activeMembership(orgSlug.value))

const isOwner = computed<boolean>(() => membership.value?.role === 'owner')

const orgDisplayName = computed<string>(() => orgSlug.value || 'Организация')

const navItems = computed<NavItem[]>(() => {
  const slug = orgSlug.value
  return [
    {
      key: 'dashboard',
      label: 'Dashboard',
      icon: LayoutDashboard,
      to: `/o/${slug}`,
      permission: null,
    },
    {
      key: 'services',
      label: 'Услуги',
      icon: Package,
      to: `/o/${slug}/services`,
      permission: 'services.edit',
    },
    {
      key: 'calendar',
      label: 'Календарь',
      icon: Calendar,
      to: `/o/${slug}/calendar`,
      permission: 'bookings.view',
    },
    {
      key: 'inbox',
      label: 'Запросы',
      icon: Inbox,
      to: `/o/${slug}/inbox`,
      permission: 'bookings.view',
    },
    {
      key: 'payouts',
      label: 'Выплаты',
      icon: Wallet,
      to: `/o/${slug}/payouts`,
      permission: 'payouts.view',
    },
    {
      key: 'team',
      label: 'Команда',
      icon: Users,
      to: `/o/${slug}/team`,
      permission: 'team.view',
    },
    {
      key: 'settings',
      label: 'Настройки',
      icon: Settings,
      to: `/o/${slug}/settings`,
      permission: 'settings.view',
      ownerOnly: true,
    },
  ]
})

/**
 * Отфильтрованные nav items: каждый с reactive computed permission check.
 * Возвращаем массив { item, allowed: ComputedRef<boolean> }.
 */
const visibleNavItems = computed<NavItem[]>(() => {
  const slug = orgSlug.value
  return navItems.value.filter((item) => {
    if (item.ownerOnly && !isOwner.value) return false
    if (item.permission === null) return true
    return authStore.canAccessOrg(slug, item.permission)
  })
})
</script>

<template>
  <div class="flex min-h-screen bg-surface-muted/30" data-test-id="org-layout">
    <aside
      class="hidden w-60 shrink-0 border-r border-border bg-surface md:flex md:flex-col"
      data-test-id="org-layout-sidebar"
    >
      <div class="flex items-center gap-2 border-b border-border px-4 py-3">
        <div
          class="flex h-8 w-8 items-center justify-center rounded-md bg-accent/10 text-sm font-semibold text-accent"
          aria-hidden="true"
        >
          {{ orgDisplayName.slice(0, 2).toUpperCase() }}
        </div>
        <div class="min-w-0 flex-1">
          <div
            class="flex items-center gap-1 truncate text-sm font-semibold text-text"
            data-test-id="org-layout-name"
          >
            <span class="truncate">{{ orgDisplayName }}</span>
            <CheckCircle2
              class="h-3.5 w-3.5 shrink-0 text-accent"
              aria-label="Verified"
              data-test-id="org-layout-verified-badge"
            />
          </div>
          <div class="truncate text-xs text-text-subtle">
            {{ membership?.role ?? 'member' }}
          </div>
        </div>
      </div>

      <nav class="flex-1 px-2 py-3" aria-label="Provider navigation">
        <ul class="flex flex-col gap-0.5">
          <li v-for="item in visibleNavItems" :key="item.key">
            <RouterLink
              :to="item.to"
              class="flex items-center gap-2 rounded-md px-3 py-2 text-sm text-text-subtle hover:bg-surface-muted hover:text-text"
              active-class="!bg-accent/10 !text-accent !font-semibold"
              :data-test-id="`org-layout-nav-${item.key}`"
            >
              <component :is="item.icon" class="h-4 w-4" aria-hidden="true" />
              <span>{{ item.label }}</span>
            </RouterLink>
          </li>
        </ul>
      </nav>
    </aside>

    <main class="flex-1 min-w-0">
      <header
        class="flex items-center gap-2 border-b border-border bg-surface px-4 py-3 md:hidden"
        data-test-id="org-layout-mobile-header"
      >
        <div class="min-w-0 flex-1">
          <div class="flex items-center gap-1 truncate text-sm font-semibold text-text">
            <span class="truncate">{{ orgDisplayName }}</span>
            <CheckCircle2 class="h-3.5 w-3.5 shrink-0 text-accent" aria-hidden="true" />
          </div>
        </div>
      </header>

      <div class="p-4 sm:p-6">
        <RouterView />
      </div>
    </main>
  </div>
</template>
