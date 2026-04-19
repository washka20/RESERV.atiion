<script setup lang="ts">
import { ref, watch } from 'vue'
import BaseInput from '@/shared/components/base/BaseInput.vue'
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

function onMinUpdate(raw: string): void {
  const parsed = raw === '' ? null : Number(raw)
  minPrice.value = parsed === null || Number.isNaN(parsed) ? null : parsed
}

function onMaxUpdate(raw: string): void {
  const parsed = raw === '' ? null : Number(raw)
  maxPrice.value = parsed === null || Number.isNaN(parsed) ? null : parsed
}
</script>

<template>
  <fieldset class="flex flex-col gap-2 text-sm">
    <legend class="mb-1 block font-medium text-text">Цена, ₽</legend>
    <div class="grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-end gap-2">
      <BaseInput
        :model-value="minPrice ?? ''"
        type="number"
        placeholder="от"
        test-id="catalog-price-filter-min"
        class="min-w-0"
        :input-attrs="{ min: 0, step: 1 }"
        @update:model-value="onMinUpdate"
        @blur="applyMin"
      />
      <span class="pb-2 text-text-subtle">—</span>
      <BaseInput
        :model-value="maxPrice ?? ''"
        type="number"
        placeholder="до"
        test-id="catalog-price-filter-max"
        class="min-w-0"
        :input-attrs="{ min: 0, step: 1 }"
        @update:model-value="onMaxUpdate"
        @blur="applyMax"
      />
    </div>
  </fieldset>
</template>
