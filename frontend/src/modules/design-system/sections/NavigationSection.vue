<script setup lang="ts">
/**
 * Navigation: WorkspaceSwitcher + BottomNav (preview).
 */
import { ref } from 'vue'
import { Home, Search, Calendar, User } from 'lucide-vue-next'
import BaseWorkspaceSwitcher from '@/shared/components/base/BaseWorkspaceSwitcher.vue'
import BaseBottomNav from '@/shared/components/base/BaseBottomNav.vue'

const workspaces = [
  { id: 'personal', name: 'Personal', type: 'personal' as const },
  { id: 'salon', name: 'Salon Savvin', type: 'organization' as const },
  { id: 'loft', name: 'Loft 23', type: 'organization' as const },
]
const activeWorkspace = ref('salon')

const navItems = [
  { id: 'home', label: 'Главная', icon: Home },
  { id: 'search', label: 'Поиск', icon: Search },
  { id: 'bookings', label: 'Брони', icon: Calendar, badge: 3 },
  { id: 'profile', label: 'Профиль', icon: User },
]
const activeNav = ref('bookings')
</script>

<template>
  <div class="flex flex-col gap-6">
    <header>
      <h2 class="text-2xl font-semibold text-text">Navigation</h2>
      <p class="text-sm text-text-subtle mt-1">
        WorkspaceSwitcher — для multi-tenant. BottomNav — мобильная (md+ скрыта глобально,
        здесь — preview через вынесенный контейнер).
      </p>
    </header>

    <div class="bg-surface border border-border rounded-md p-6 flex flex-col gap-3">
      <span class="font-mono text-xs text-text-subtle uppercase tracking-wide">WorkspaceSwitcher</span>
      <div>
        <BaseWorkspaceSwitcher v-model="activeWorkspace" :workspaces="workspaces" />
      </div>
      <p class="text-xs text-text-subtle">
        Активный workspace: <span class="font-mono">{{ activeWorkspace }}</span>
      </p>
    </div>

    <div class="bg-surface border border-border rounded-md p-6 flex flex-col gap-3">
      <span class="font-mono text-xs text-text-subtle uppercase tracking-wide">BottomNav (mobile preview)</span>
      <div class="relative mx-auto w-full max-w-xs h-28 border border-border rounded-md bg-bg overflow-hidden">
        <div class="absolute inset-x-0 bottom-0 border-t border-border bg-surface">
          <ul class="grid grid-cols-4 gap-1">
            <li v-for="item in navItems" :key="item.id">
              <button
                type="button"
                class="relative w-full flex flex-col items-center justify-center gap-0.5 py-2 text-xs transition-colors focus:outline-none focus-visible:bg-surface-muted"
                :class="
                  activeNav === item.id
                    ? 'text-accent'
                    : 'text-text-subtle hover:text-text'
                "
                @click="activeNav = item.id"
              >
                <span class="relative inline-flex">
                  <component :is="item.icon" class="w-5 h-5" aria-hidden="true" />
                  <span
                    v-if="item.badge !== undefined && item.badge > 0"
                    class="absolute -top-1 -right-2 inline-flex items-center justify-center min-w-[16px] h-4 px-1 rounded-full bg-danger text-white text-[10px] font-semibold"
                  >
                    {{ item.badge }}
                  </span>
                </span>
                <span>{{ item.label }}</span>
              </button>
            </li>
          </ul>
        </div>
      </div>
      <p class="text-xs text-text-subtle">
        Активный: <span class="font-mono">{{ activeNav }}</span>. На md+ реальный
        &lt;BaseBottomNav&gt; автоматически скрывается — компонент ниже использует обычный
        viewport-rendering.
      </p>
    </div>

    <!-- Настоящий BaseBottomNav — показывается только на <md (как в production) -->
    <BaseBottomNav v-model="activeNav" :items="navItems" />

    <pre class="font-mono text-xs bg-surface-muted p-3 rounded-md overflow-x-auto"><code>&lt;BaseWorkspaceSwitcher v-model="ws" :workspaces="workspaces" /&gt;
&lt;BaseBottomNav v-model="nav" :items="items" /&gt;</code></pre>
  </div>
</template>
