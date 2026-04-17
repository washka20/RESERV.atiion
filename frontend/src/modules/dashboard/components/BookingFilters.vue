<script setup lang="ts">
defineProps<{ modelValue: string }>()
defineEmits<{ 'update:modelValue': [value: string] }>()

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
</script>

<template>
  <label class="flex items-center gap-2 text-sm text-gray-600">
    <span>Статус</span>
    <select
      :value="modelValue"
      class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-700 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
      data-test-id="dashboard-filter-status"
      @change="$emit('update:modelValue', ($event.target as HTMLSelectElement).value)"
    >
      <option v-for="opt in options" :key="opt.value" :value="opt.value">
        {{ opt.label }}
      </option>
    </select>
  </label>
</template>
