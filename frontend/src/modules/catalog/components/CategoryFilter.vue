<script setup lang="ts">
import { computed } from 'vue'
import { useCatalogStore } from '@/stores/catalog.store'

const store = useCatalogStore()

const selected = computed<string>({
  get: () => store.filters.categoryId ?? '',
  set: (value) => {
    store.setFilters({ categoryId: value === '' ? null : value })
    void store.fetchServices()
  },
})
</script>

<template>
  <label class="block text-sm">
    <span class="mb-1 block font-medium text-gray-700">Категория</span>
    <select
      v-model="selected"
      class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
      data-test-id="catalog-category-filter-select"
    >
      <option value="">Все категории</option>
      <option
        v-for="category in store.categories"
        :key="category.id"
        :value="category.id"
      >
        {{ category.name }}
      </option>
    </select>
  </label>
</template>
