<script setup lang="ts">
/**
 * Многострочное текстовое поле — аналог BaseInput + rows.
 */
import { computed, useId } from 'vue'

interface Props {
  modelValue?: string
  label?: string
  error?: string
  helper?: string
  placeholder?: string
  disabled?: boolean
  required?: boolean
  id?: string
  rows?: number
  readonly?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: '',
  disabled: false,
  required: false,
  rows: 4,
  readonly: false,
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
  blur: [event: FocusEvent]
  focus: [event: FocusEvent]
}>()

const autoId = useId()
const inputId = computed<string>(() => props.id ?? `base-textarea-${autoId}`)
const describedBy = computed<string | undefined>(() => {
  if (props.error) return `${inputId.value}-error`
  if (props.helper) return `${inputId.value}-helper`
  return undefined
})

const onInput = (event: Event) => {
  const target = event.target as HTMLTextAreaElement
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
    <textarea
      :id="inputId"
      :value="modelValue"
      :placeholder="placeholder"
      :disabled="disabled"
      :required="required"
      :readonly="readonly"
      :rows="rows"
      :aria-invalid="!!error || undefined"
      :aria-describedby="describedBy"
      class="rounded-md border bg-surface px-3 py-2 text-base text-text placeholder:text-text-subtle transition-colors focus:outline-none disabled:cursor-not-allowed disabled:opacity-60"
      :class="error ? 'border-danger' : 'border-border focus:border-accent'"
      :data-test-id="`base-textarea-${id ?? autoId}`"
      @input="onInput"
      @blur="(event) => emit('blur', event)"
      @focus="(event) => emit('focus', event)"
    />
    <p v-if="error" :id="`${inputId}-error`" class="text-sm text-danger">{{ error }}</p>
    <p v-else-if="helper" :id="`${inputId}-helper`" class="text-sm text-text-subtle">{{ helper }}</p>
  </div>
</template>
