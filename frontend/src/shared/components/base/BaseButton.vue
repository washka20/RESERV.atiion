<script setup lang="ts">
/**
 * Универсальная кнопка с вариантами стиля и loading-состоянием.
 *
 * Variants: primary | secondary | ghost | danger.
 * Sizes: sm | md | lg.
 * При loading=true показывает BaseSpinner и блокирует click.
 */
import { computed } from 'vue'
import BaseSpinner from './BaseSpinner.vue'

type Variant = 'primary' | 'secondary' | 'ghost' | 'danger'
type Size = 'sm' | 'md' | 'lg'

interface Props {
  variant?: Variant
  size?: Size
  loading?: boolean
  disabled?: boolean
  type?: 'button' | 'submit' | 'reset'
  fullWidth?: boolean
  /** Явно переопределяет `data-test-id` кнопки (для сохранения e2e локаторов). */
  testId?: string
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'primary',
  size: 'md',
  loading: false,
  disabled: false,
  type: 'button',
  fullWidth: false,
})

const emit = defineEmits<{
  click: [event: MouseEvent]
}>()

const variantClass = computed<string>(() => {
  const map: Record<Variant, string> = {
    primary:
      'bg-accent text-white hover:opacity-90 active:opacity-80 border border-transparent',
    secondary:
      'bg-surface text-text border border-border hover:bg-surface-muted',
    ghost:
      'bg-transparent text-text hover:bg-surface-muted border border-transparent',
    danger:
      'bg-danger text-white hover:opacity-90 active:opacity-80 border border-transparent',
  }
  return map[props.variant]
})

const sizeClass = computed<string>(() => {
  const map: Record<Size, string> = {
    sm: 'h-8 px-3 text-sm gap-1.5',
    md: 'h-10 px-4 text-base gap-2',
    lg: 'h-12 px-6 text-lg gap-2',
  }
  return map[props.size]
})

const isDisabled = computed<boolean>(() => props.disabled || props.loading)

const spinnerSize = computed<'sm' | 'md'>(() => (props.size === 'lg' ? 'md' : 'sm'))

const handleClick = (event: MouseEvent) => {
  if (isDisabled.value) {
    event.preventDefault()
    event.stopPropagation()
    return
  }
  emit('click', event)
}
</script>

<template>
  <button
    :type="type"
    :disabled="isDisabled"
    :aria-busy="loading || undefined"
    :aria-disabled="isDisabled || undefined"
    :class="[
      'inline-flex items-center justify-center rounded-md font-medium',
      'transition-opacity transition-colors focus-visible:outline-none',
      'disabled:cursor-not-allowed disabled:opacity-60',
      variantClass,
      sizeClass,
      fullWidth ? 'w-full' : '',
    ]"
    :data-test-id="testId ?? `base-button-${variant}`"
    @click="handleClick"
  >
    <BaseSpinner v-if="loading" :size="spinnerSize" />
    <span v-else-if="$slots['icon-left']" class="inline-flex items-center">
      <slot name="icon-left" />
    </span>
    <span class="inline-flex items-center"><slot /></span>
    <span v-if="$slots['icon-right'] && !loading" class="inline-flex items-center">
      <slot name="icon-right" />
    </span>
  </button>
</template>
