<script setup lang="ts">
/**
 * Текстовое поле с меткой, подсказкой, ошибкой и slot-ами prefix/suffix.
 *
 * v-model через `update:modelValue`.
 * Если id не указан — генерируется через useId() (Vue 3.5+).
 */
import { computed, useId } from 'vue'

interface Props {
  modelValue?: string | number
  label?: string
  error?: string
  helper?: string
  placeholder?: string
  type?: string
  disabled?: boolean
  required?: boolean
  id?: string
  autocomplete?: string
  readonly?: boolean
  /**
   * Явно переопределяет `data-test-id` нативного input.
   * Используется при миграции существующих форм, чтобы сохранить e2e локаторы.
   */
  testId?: string
  /**
   * Дополнительные атрибуты для нативного input (min, max, step, pattern и т.п.).
   */
  inputAttrs?: Record<string, string | number | boolean>
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: '',
  type: 'text',
  disabled: false,
  required: false,
  readonly: false,
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
  blur: [event: FocusEvent]
  focus: [event: FocusEvent]
}>()

const autoId = useId()
const inputId = computed<string>(() => props.id ?? `base-input-${autoId}`)
const describedBy = computed<string | undefined>(() => {
  if (props.error) return `${inputId.value}-error`
  if (props.helper) return `${inputId.value}-helper`
  return undefined
})

const onInput = (event: Event) => {
  const target = event.target as HTMLInputElement
  emit('update:modelValue', target.value)
}
</script>

<template>
  <div class="flex flex-col gap-1">
    <label
      v-if="label"
      :for="inputId"
      class="text-sm font-medium text-text"
    >
      {{ label }}
      <span v-if="required" class="text-danger" aria-hidden="true">*</span>
    </label>
    <div
      class="flex items-center gap-2 rounded-md border bg-surface transition-colors"
      :class="[
        error ? 'border-danger' : 'border-border',
        disabled ? 'opacity-60 cursor-not-allowed' : 'focus-within:border-accent',
      ]"
    >
      <span v-if="$slots.prefix" class="pl-3 text-text-subtle inline-flex items-center">
        <slot name="prefix" />
      </span>
      <input
        :id="inputId"
        :type="type"
        :value="modelValue"
        :placeholder="placeholder"
        :disabled="disabled"
        :required="required"
        :readonly="readonly"
        :autocomplete="autocomplete"
        :aria-invalid="!!error || undefined"
        :aria-describedby="describedBy"
        class="flex-1 min-w-0 bg-transparent px-3 py-2 text-base text-text placeholder:text-text-subtle focus:outline-none disabled:cursor-not-allowed"
        :data-test-id="testId ?? `base-input-${id ?? autoId}`"
        v-bind="inputAttrs"
        @input="onInput"
        @blur="(event) => emit('blur', event)"
        @focus="(event) => emit('focus', event)"
      >
      <span v-if="$slots.suffix" class="pr-3 text-text-subtle inline-flex items-center">
        <slot name="suffix" />
      </span>
    </div>
    <p
      v-if="error"
      :id="`${inputId}-error`"
      class="text-sm text-danger"
    >
      {{ error }}
    </p>
    <p
      v-else-if="helper"
      :id="`${inputId}-helper`"
      class="text-sm text-text-subtle"
    >
      {{ helper }}
    </p>
  </div>
</template>
