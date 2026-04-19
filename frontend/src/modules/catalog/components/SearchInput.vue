<script setup lang="ts">
import { ref, onBeforeUnmount, watch } from 'vue'
import BaseInput from '@/shared/components/base/BaseInput.vue'
import { useCatalogStore } from '@/stores/catalog.store'

const DEBOUNCE_MS = 300

const store = useCatalogStore()
const value = ref<string>(store.filters.search)
let timer: ReturnType<typeof setTimeout> | null = null

watch(
  () => store.filters.search,
  (next) => {
    if (next !== value.value) value.value = next
  },
)

function schedule(): void {
  if (timer !== null) clearTimeout(timer)
  timer = setTimeout(() => {
    store.setFilters({ search: value.value.trim() })
    void store.fetchServices()
    timer = null
  }, DEBOUNCE_MS)
}

onBeforeUnmount(() => {
  if (timer !== null) clearTimeout(timer)
})
</script>

<template>
  <BaseInput
    v-model="value"
    label="Поиск"
    type="search"
    placeholder="Название услуги..."
    test-id="catalog-search-input"
    @update:model-value="schedule"
  />
</template>
