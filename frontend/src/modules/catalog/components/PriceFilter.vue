<script setup lang="ts">
import { ref, watch } from 'vue'
import { useCatalogStore } from '@/stores/catalog.store'

const store = useCatalogStore()

/** Перевод копеек из стора в рубли для UI (или null при отсутствии значения). */
function fromMinor(value: number | null): number | null {
  if (value === null) return null
  return value / 100
}

/** Перевод рублей UI в копейки для backend (или null при отсутствии значения). */
function toMinor(value: number | null): number | null {
  if (value === null || Number.isNaN(value)) return null
  return Math.round(value * 100)
}

const minPrice = ref<number | null>(fromMinor(store.filters.minPrice))
const maxPrice = ref<number | null>(fromMinor(store.filters.maxPrice))

watch(
  () => store.filters.minPrice,
  (value) => {
    const next = fromMinor(value)
    if (next !== minPrice.value) minPrice.value = next
  },
)

watch(
  () => store.filters.maxPrice,
  (value) => {
    const next = fromMinor(value)
    if (next !== maxPrice.value) maxPrice.value = next
  },
)

function applyMin(): void {
  store.setFilters({ minPrice: toMinor(minPrice.value) })
  void store.fetchServices()
}

function applyMax(): void {
  store.setFilters({ maxPrice: toMinor(maxPrice.value) })
  void store.fetchServices()
}
</script>

<template>
  <fieldset class="space-y-2 text-sm">
    <legend class="mb-1 block font-medium text-gray-700">Цена, ₽</legend>
    <div class="flex items-center gap-2">
      <input
        v-model.number="minPrice"
        type="number"
        min="0"
        step="1"
        placeholder="от"
        class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
        data-test-id="catalog-price-filter-min"
        @blur="applyMin"
      />
      <span class="text-gray-400">—</span>
      <input
        v-model.number="maxPrice"
        type="number"
        min="0"
        step="1"
        placeholder="до"
        class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
        data-test-id="catalog-price-filter-max"
        @blur="applyMax"
      />
    </div>
  </fieldset>
</template>
