<script setup lang="ts">
import { computed } from 'vue'
import BaseSelect from '@/shared/components/base/BaseSelect.vue'
import { useCatalogStore } from '@/stores/catalog.store'

const store = useCatalogStore()

const selected = computed<string>({
  get: () => store.filters.categoryId ?? '',
  set: (value) => {
    store.setFilters({ categoryId: value === '' ? null : value })
    void store.fetchServices()
  },
})

const options = computed(() => [
  { value: '', label: 'Все категории' },
  ...store.categories.map((category) => ({
    value: category.id,
    label: category.name,
  })),
])
</script>

<template>
  <BaseSelect
    v-model="selected"
    :options="options"
    label="Категория"
    test-id="catalog-category-filter-select"
  />
</template>
