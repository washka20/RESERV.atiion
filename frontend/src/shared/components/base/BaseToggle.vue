<script setup lang="ts">
/**
 * Toggle-switch — ARIA role="switch".
 * Клавиатура: Space/Enter переключают состояние.
 */
import { computed, useId } from 'vue'

interface Props {
  modelValue?: boolean
  label?: string
  disabled?: boolean
  id?: string
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: false,
  disabled: false,
})

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
}>()

const autoId = useId()
const toggleId = computed<string>(() => props.id ?? `base-toggle-${autoId}`)

const toggle = () => {
  if (props.disabled) return
  emit('update:modelValue', !props.modelValue)
}

const onKeyDown = (event: KeyboardEvent) => {
  if (event.key === ' ' || event.key === 'Enter') {
    event.preventDefault()
    toggle()
  }
}
</script>

<template>
  <label
    :for="toggleId"
    class="inline-flex items-center gap-3 cursor-pointer select-none"
    :class="disabled ? 'opacity-60 cursor-not-allowed' : ''"
  >
    <button
      :id="toggleId"
      type="button"
      role="switch"
      :aria-checked="modelValue"
      :aria-label="label"
      :disabled="disabled"
      class="relative inline-flex items-center h-6 w-11 rounded-full border border-border transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
      :class="modelValue ? 'bg-accent border-accent' : 'bg-surface-muted'"
      :data-test-id="`base-toggle-${id ?? autoId}`"
      @click="toggle"
      @keydown="onKeyDown"
    >
      <span
        class="inline-block w-5 h-5 rounded-full bg-white shadow-sm transition-transform"
        :class="modelValue ? 'translate-x-5' : 'translate-x-0.5'"
      />
    </button>
    <span v-if="label" class="text-base text-text">{{ label }}</span>
  </label>
</template>
