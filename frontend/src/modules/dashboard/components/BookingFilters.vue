<script setup lang="ts">
import BaseSelect from '@/shared/components/base/BaseSelect.vue'

defineProps<{ modelValue: string }>()
const emit = defineEmits<{ 'update:modelValue': [value: string] }>()

interface FilterOption {
  value: string
  label: string
}

const options: FilterOption[] = [
  { value: 'all', label: 'Все' },
  { value: 'pending', label: 'В обработке' },
  { value: 'confirmed', label: 'Подтверждённые' },
  { value: 'cancelled', label: 'Отменённые' },
  { value: 'completed', label: 'Выполненные' },
]

function onUpdate(value: string | number): void {
  emit('update:modelValue', String(value))
}
</script>

<template>
  <div class="flex items-center gap-2 text-sm">
    <span class="text-text-subtle">Статус</span>
    <BaseSelect
      :model-value="modelValue"
      :options="options"
      test-id="dashboard-filter-status"
      @update:model-value="onUpdate"
    />
  </div>
</template>
