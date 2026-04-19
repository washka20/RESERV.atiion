<script setup lang="ts">
/**
 * Мобильная нижняя навигация. На md+ скрыта. Badge показывает число уведомлений.
 */
import type { Component } from 'vue'

interface NavItem {
  id: string
  label: string
  icon: Component
  badge?: number
}

interface Props {
  items: NavItem[]
  modelValue: string
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const select = (id: string) => {
  if (id === props.modelValue) return
  emit('update:modelValue', id)
}
</script>

<template>
  <nav
    class="fixed bottom-0 inset-x-0 z-30 bg-surface border-t border-border md:hidden"
    aria-label="Bottom navigation"
    data-test-id="base-bottom-nav"
  >
    <ul class="grid grid-cols-4 gap-1">
      <li v-for="item in items" :key="item.id">
        <button
          type="button"
          class="relative w-full flex flex-col items-center justify-center gap-0.5 py-2 text-xs transition-colors focus:outline-none focus-visible:bg-surface-muted"
          :class="
            modelValue === item.id
              ? 'text-accent'
              : 'text-text-subtle hover:text-text'
          "
          :aria-current="modelValue === item.id ? 'page' : undefined"
          :data-test-id="`base-bottom-nav-item-${item.id}`"
          @click="select(item.id)"
        >
          <span class="relative inline-flex">
            <component :is="item.icon" class="w-5 h-5" aria-hidden="true" />
            <span
              v-if="item.badge !== undefined && item.badge > 0"
              class="absolute -top-1 -right-2 inline-flex items-center justify-center min-w-[16px] h-4 px-1 rounded-full bg-danger text-white text-[10px] font-semibold"
              :data-test-id="`base-bottom-nav-badge-${item.id}`"
            >
              {{ item.badge > 99 ? '99+' : item.badge }}
            </span>
          </span>
          <span>{{ item.label }}</span>
        </button>
      </li>
    </ul>
  </nav>
</template>
