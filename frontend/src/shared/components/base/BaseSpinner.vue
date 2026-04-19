<script setup lang="ts">
/**
 * Анимированный spinner для loading-состояний.
 * ARIA: role=status, aria-label для скрин-ридеров.
 */
import { computed } from 'vue'

interface Props {
  size?: 'sm' | 'md' | 'lg'
  label?: string
}

const props = withDefaults(defineProps<Props>(), {
  size: 'md',
  label: 'Загрузка',
})

const sizeClass = computed(() => {
  const map: Record<NonNullable<Props['size']>, string> = {
    sm: 'w-4 h-4 border-2',
    md: 'w-5 h-5 border-2',
    lg: 'w-8 h-8 border-[3px]',
  }
  return map[props.size]
})
</script>

<template>
  <span
    class="inline-block rounded-full border-current border-t-transparent animate-spin align-middle"
    :class="sizeClass"
    role="status"
    :aria-label="label"
    data-test-id="base-spinner"
  />
</template>
