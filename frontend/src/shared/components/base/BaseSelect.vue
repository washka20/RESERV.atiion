<script setup lang="ts">
/**
 * Обёртка над native <select> с label/error и placeholder-опцией.
 */
import { computed, useId } from 'vue'

interface Option {
  value: string | number
  label: string
  disabled?: boolean
}

interface Props {
  modelValue?: string | number | null
  options: Option[]
  label?: string
  error?: string
  helper?: string
  placeholder?: string
  disabled?: boolean
  required?: boolean
  id?: string
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: '',
  disabled: false,
  required: false,
})

const emit = defineEmits<{
  'update:modelValue': [value: string | number]
}>()

const autoId = useId()
const selectId = computed<string>(() => props.id ?? `base-select-${autoId}`)

const onChange = (event: Event) => {
  const target = event.target as HTMLSelectElement
  emit('update:modelValue', target.value)
}
</script>

<template>
  <div class="flex flex-col gap-1">
    <label
      v-if="label"
      :for="selectId"
      class="text-sm font-medium text-text"
    >
      {{ label }}
      <span v-if="required" class="text-danger" aria-hidden="true">*</span>
    </label>
    <select
      :id="selectId"
      :value="modelValue ?? ''"
      :disabled="disabled"
      :required="required"
      :aria-invalid="!!error || undefined"
      class="h-10 rounded-md border bg-surface px-3 text-base text-text transition-colors focus:outline-none disabled:cursor-not-allowed disabled:opacity-60"
      :class="error ? 'border-danger' : 'border-border focus:border-accent'"
      :data-test-id="`base-select-${id ?? autoId}`"
      @change="onChange"
    >
      <option v-if="placeholder" value="" disabled>{{ placeholder }}</option>
      <option
        v-for="opt in options"
        :key="String(opt.value)"
        :value="opt.value"
        :disabled="opt.disabled"
      >
        {{ opt.label }}
      </option>
    </select>
    <p v-if="error" class="text-sm text-danger">{{ error }}</p>
    <p v-else-if="helper" class="text-sm text-text-subtle">{{ helper }}</p>
  </div>
</template>
