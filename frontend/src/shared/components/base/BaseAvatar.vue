<script setup lang="ts">
/**
 * Аватар с автоматическим fallback-на-инициалы при отсутствии src или ошибке загрузки.
 */
import { computed, ref, watch } from 'vue'

type Size = 'sm' | 'md' | 'lg' | 'xl'
type Shape = 'round' | 'square'

interface Props {
  src?: string | null
  alt: string
  fallback?: string
  size?: Size
  shape?: Shape
}

const props = withDefaults(defineProps<Props>(), {
  size: 'md',
  shape: 'round',
})

const imageFailed = ref(false)

watch(
  () => props.src,
  () => {
    imageFailed.value = false
  },
)

const sizeClass = computed<string>(() => {
  const map: Record<Size, string> = {
    sm: 'w-8 h-8 text-xs',
    md: 'w-10 h-10 text-sm',
    lg: 'w-14 h-14 text-base',
    xl: 'w-20 h-20 text-lg',
  }
  return map[props.size]
})

const shapeClass = computed<string>(() =>
  props.shape === 'round' ? 'rounded-full' : 'rounded-md',
)

const initials = computed<string>(() => {
  if (props.fallback) return props.fallback.slice(0, 2).toUpperCase()
  const parts = props.alt.trim().split(/\s+/).filter(Boolean)
  if (!parts.length) return '?'
  if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase()
  return `${parts[0][0]}${parts[1][0]}`.toUpperCase()
})

const showImage = computed<boolean>(() => !!props.src && !imageFailed.value)
</script>

<template>
  <span
    class="inline-flex items-center justify-center overflow-hidden bg-surface-muted text-text select-none border border-border"
    :class="[sizeClass, shapeClass]"
    :aria-label="alt"
    data-test-id="base-avatar"
  >
    <img
      v-if="showImage"
      :src="src ?? undefined"
      :alt="alt"
      class="w-full h-full object-cover"
      data-test-id="base-avatar-img"
      @error="imageFailed = true"
    >
    <span
      v-else
      class="font-semibold"
      data-test-id="base-avatar-fallback"
      aria-hidden="true"
    >
      {{ initials }}
    </span>
  </span>
</template>
