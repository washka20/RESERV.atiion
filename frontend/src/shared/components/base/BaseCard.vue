<script setup lang="ts">
/**
 * Контейнер-карточка. Padding / elevation настраиваются через props.
 */
import { computed } from 'vue'

type Padding = 'sm' | 'md' | 'lg'
type Elevation = 'none' | 'sm' | 'md'

interface Props {
  padding?: Padding
  elevation?: Elevation
  as?: 'div' | 'section' | 'article' | 'aside'
}

const props = withDefaults(defineProps<Props>(), {
  padding: 'md',
  elevation: 'sm',
  as: 'div',
})

const paddingClass = computed<string>(() => {
  const map: Record<Padding, string> = {
    sm: 'p-3',
    md: 'p-4',
    lg: 'p-6',
  }
  return map[props.padding]
})

const elevationClass = computed<string>(() => {
  const map: Record<Elevation, string> = {
    none: '',
    sm: 'shadow-sm',
    md: 'shadow-md',
  }
  return map[props.elevation]
})
</script>

<template>
  <component
    :is="as"
    class="bg-surface rounded-md border border-border"
    :class="[paddingClass, elevationClass]"
    data-test-id="base-card"
  >
    <slot />
  </component>
</template>
