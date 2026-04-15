<script setup lang="ts">
import { computed } from 'vue'
import { useCatalogStore } from '@/stores/catalog.store'
import type { ServiceType } from '@/types/catalog.types'

const store = useCatalogStore()

const selected = computed<string>({
  get: () => store.filters.type ?? '',
  set: (value) => {
    const normalized = value === '' ? null : (value as ServiceType)
    store.setFilters({ type: normalized })
    void store.fetchServices()
  },
})
</script>

<template>
  <label class="block text-sm">
    <span class="mb-1 block font-medium text-gray-700">Тип</span>
    <select
      v-model="selected"
      class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
      data-test-id="catalog-type-filter-select"
    >
      <option value="">Все</option>
      <option value="time_slot">Временной слот</option>
      <option value="quantity">Количество</option>
    </select>
  </label>
</template>
