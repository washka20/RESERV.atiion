<script setup lang="ts">
/**
 * Chip — компактный элемент фильтра / тега.
 * Клавиатура: Enter/Space → click; Delete/Backspace → remove (если removable).
 */
import { computed } from 'vue'

interface Props {
  label?: string
  removable?: boolean
  clickable?: boolean
  selected?: boolean
  disabled?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  removable: false,
  clickable: false,
  selected: false,
  disabled: false,
})

const emit = defineEmits<{
  click: [event: Event]
  remove: [event: Event]
}>()

const isInteractive = computed<boolean>(
  () => props.clickable || props.removable,
)

const onClick = (event: Event) => {
  if (props.disabled) return
  if (props.clickable) emit('click', event)
}

const onKeyDown = (event: KeyboardEvent) => {
  if (props.disabled) return
  if (props.clickable && (event.key === 'Enter' || event.key === ' ')) {
    event.preventDefault()
    emit('click', event)
    return
  }
  if (
    props.removable &&
    (event.key === 'Delete' || event.key === 'Backspace')
  ) {
    event.preventDefault()
    emit('remove', event)
  }
}

const onRemoveClick = (event: Event) => {
  if (props.disabled) return
  event.stopPropagation()
  emit('remove', event)
}
</script>

<template>
  <span
    :role="isInteractive ? 'button' : undefined"
    :tabindex="isInteractive && !disabled ? 0 : undefined"
    :aria-pressed="clickable ? selected : undefined"
    :aria-disabled="disabled || undefined"
    class="inline-flex items-center gap-1 rounded-sm border px-2 py-1 text-sm transition-colors"
    :class="[
      selected
        ? 'bg-accent text-white border-accent'
        : 'bg-surface-muted text-text border-border',
      isInteractive && !disabled ? 'cursor-pointer hover:border-accent' : '',
      disabled ? 'opacity-60 cursor-not-allowed' : '',
    ]"
    data-test-id="base-chip"
    @click="onClick"
    @keydown="onKeyDown"
  >
    <slot>{{ label }}</slot>
    <button
      v-if="removable"
      type="button"
      class="inline-flex items-center justify-center w-4 h-4 rounded-full hover:bg-black/10"
      aria-label="Удалить"
      data-test-id="base-chip-remove"
      @click="onRemoveClick"
    >
      <svg
        viewBox="0 0 12 12"
        class="w-3 h-3"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        aria-hidden="true"
      >
        <path d="M3 3 L9 9 M9 3 L3 9" />
      </svg>
    </button>
  </span>
</template>
