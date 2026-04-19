<script setup lang="ts">
import { computed } from 'vue'
import BaseSelect from '@/shared/components/base/BaseSelect.vue'
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

const options = [
  { value: '', label: 'Все' },
  { value: 'time_slot', label: 'Временной слот' },
  { value: 'quantity', label: 'Количество' },
]
</script>

<template>
  <BaseSelect
    v-model="selected"
    :options="options"
    label="Тип"
    test-id="catalog-type-filter-select"
  />
</template>
