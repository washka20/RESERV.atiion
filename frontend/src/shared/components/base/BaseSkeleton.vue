<script setup lang="ts">
/**
 * Skeleton-плейсхолдер. Varianty: text (N линий), card, circle, custom.
 * width / height — для custom/circle кастомные размеры.
 */
import { computed } from 'vue'

type Variant = 'text' | 'card' | 'circle' | 'custom'

interface Props {
  variant?: Variant
  width?: string
  height?: string
  lines?: number
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'text',
  lines: 3,
})

const pulseClass = 'animate-pulse bg-surface-muted rounded-sm'

const style = computed<Record<string, string>>(() => {
  const s: Record<string, string> = {}
  if (props.width) s.width = props.width
  if (props.height) s.height = props.height
  return s
})
</script>

<template>
  <div
    v-if="variant === 'text'"
    class="flex flex-col gap-2"
    data-test-id="base-skeleton"
    aria-hidden="true"
  >
    <div
      v-for="i in lines"
      :key="i"
      class="h-4"
      :class="pulseClass"
      :style="i === lines ? { width: '60%' } : undefined"
      data-test-id="base-skeleton-line"
    />
  </div>
  <div
    v-else-if="variant === 'card'"
    class="w-full h-40"
    :class="pulseClass"
    data-test-id="base-skeleton"
    aria-hidden="true"
  />
  <div
    v-else-if="variant === 'circle'"
    class="rounded-full animate-pulse bg-surface-muted"
    :style="{
      width: width ?? '2.5rem',
      height: height ?? '2.5rem',
    }"
    data-test-id="base-skeleton"
    aria-hidden="true"
  />
  <div
    v-else
    :class="pulseClass"
    :style="style"
    data-test-id="base-skeleton"
    aria-hidden="true"
  />
</template>
