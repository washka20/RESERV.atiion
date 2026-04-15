<script setup lang="ts">
import { ref, onBeforeUnmount, watch } from 'vue'
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
  <label class="block text-sm">
    <span class="mb-1 block font-medium text-gray-700">Поиск</span>
    <input
      v-model="value"
      type="search"
      placeholder="Название услуги..."
      class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
      data-test-id="catalog-search-input"
      @input="schedule"
    />
  </label>
</template>
