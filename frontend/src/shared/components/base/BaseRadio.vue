<script setup lang="ts">
/**
 * Radio. Группа объединяется одинаковым `name`.
 * v-model двусторонний — текущее выбранное значение сравнивается с `value`.
 */
import { computed, useId } from 'vue'

interface Props {
  modelValue?: string | number | null
  value: string | number
  name: string
  label?: string
  disabled?: boolean
  id?: string
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: null,
  disabled: false,
})

const emit = defineEmits<{
  'update:modelValue': [value: string | number]
}>()

const autoId = useId()
const inputId = computed<string>(() => props.id ?? `base-radio-${autoId}`)
const isChecked = computed<boolean>(() => props.modelValue === props.value)

const onChange = () => {
  emit('update:modelValue', props.value)
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
        type="radio"
        :name="name"
        :value="value"
        :checked="isChecked"
        :disabled="disabled"
        :aria-checked="isChecked"
        class="peer sr-only"
        :data-test-id="`base-radio-${id ?? autoId}`"
        @change="onChange"
      >
      <span
        class="w-5 h-5 rounded-full border border-border bg-surface flex items-center justify-center transition-colors peer-checked:border-accent"
      >
        <span
          v-if="isChecked"
          class="w-2.5 h-2.5 rounded-full bg-accent"
        />
      </span>
    </span>
    <span v-if="label" class="text-base text-text">{{ label }}</span>
  </label>
</template>
