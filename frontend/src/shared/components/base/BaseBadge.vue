<script setup lang="ts">
/**
 * Небольшой inline badge / pill для статусов.
 */
import { computed } from 'vue'

type Variant = 'neutral' | 'success' | 'warning' | 'danger' | 'info'

interface Props {
  variant?: Variant
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'neutral',
})

const variantClass = computed<string>(() => {
  const map: Record<Variant, string> = {
    neutral: 'bg-surface-muted text-text',
    success: 'bg-success/15 text-success',
    warning: 'bg-warning/15 text-warning',
    danger: 'bg-danger/15 text-danger',
    info: 'bg-accent-soft text-accent',
  }
  return map[props.variant]
})
</script>

<template>
  <span
    class="inline-flex items-center rounded-sm px-2 py-0.5 text-xs font-medium"
    :class="variantClass"
    :data-test-id="`base-badge-${variant}`"
  >
    <slot />
  </span>
</template>
