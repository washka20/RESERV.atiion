<script setup lang="ts">
/**
 * Чекбокс с кастомной визуалкой поверх native <input type="checkbox">.
 */
import { computed, useId } from 'vue'

interface Props {
  modelValue?: boolean
  label?: string
  disabled?: boolean
  id?: string
  required?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: false,
  disabled: false,
  required: false,
})

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
}>()

const autoId = useId()
const inputId = computed<string>(() => props.id ?? `base-checkbox-${autoId}`)

const onChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  emit('update:modelValue', target.checked)
}
</script>

<template>
  <label
    :for="inputId"
    class="inline-flex items-center gap-2 cursor-pointer select-none"
    :class="disabled ? 'opacity-60 cursor-not-allowed' : ''"
  >
    <span class="relative inline-flex items-center">
      <input
        :id="inputId"
        type="checkbox"
        :checked="modelValue"
        :disabled="disabled"
        :required="required"
        :aria-checked="modelValue"
        class="peer sr-only"
        :data-test-id="`base-checkbox-${id ?? autoId}`"
        @change="onChange"
      >
      <span
        class="w-5 h-5 rounded-sm border border-border bg-surface flex items-center justify-center transition-colors peer-checked:bg-accent peer-checked:border-accent peer-focus-visible:ring-2 peer-focus-visible:ring-accent"
      >
        <svg
          v-if="modelValue"
          viewBox="0 0 20 20"
          class="w-3.5 h-3.5 text-white"
          fill="none"
          stroke="currentColor"
          stroke-width="3"
          stroke-linecap="round"
          stroke-linejoin="round"
          aria-hidden="true"
        >
          <polyline points="4 10 8 14 16 6" />
        </svg>
      </span>
    </span>
    <span v-if="label" class="text-base text-text">{{ label }}</span>
  </label>
</template>
