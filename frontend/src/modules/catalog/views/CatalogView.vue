<script setup lang="ts">
import { onMounted } from 'vue'
import BaseButton from '@/shared/components/base/BaseButton.vue'
import { useCatalogStore } from '@/stores/catalog.store'
import ServiceList from '../components/ServiceList.vue'
import CategoryFilter from '../components/CategoryFilter.vue'
import ServiceTypeFilter from '../components/ServiceTypeFilter.vue'
import PriceFilter from '../components/PriceFilter.vue'
import SearchInput from '../components/SearchInput.vue'
import Pagination from '../components/CatalogPagination.vue'

const store = useCatalogStore()

onMounted(async () => {
  await Promise.all([store.fetchCategories(), store.fetchServices()])
})

function handlePageChange(page: number): void {
  store.setPage(page)
  void store.fetchServices()
}

function handleResetFilters(): void {
  store.resetFilters()
  void store.fetchServices()
}
</script>

<template>
  <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8" data-test-id="catalog-page">
    <header class="mb-6">
      <h1 class="text-2xl font-bold text-text">Каталог услуг</h1>
      <p class="mt-1 text-sm text-text-subtle">Выберите услугу для бронирования</p>
    </header>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[280px_1fr]">
      <aside
        class="flex flex-col gap-4 rounded-md border border-border bg-surface p-4 shadow-sm lg:sticky lg:top-4 lg:h-fit"
        data-test-id="catalog-filters"
      >
        <h2 class="text-sm font-semibold uppercase tracking-wide text-text-subtle">Фильтры</h2>

        <SearchInput />
        <CategoryFilter />
        <ServiceTypeFilter />
        <PriceFilter />

        <BaseButton
          variant="secondary"
          full-width
          test-id="catalog-reset-filters-btn"
          @click="handleResetFilters"
        >
          Сбросить
        </BaseButton>
      </aside>

      <main class="space-y-6">
        <ServiceList
          :services="store.services"
          :is-loading="store.isLoading"
          :error="store.error"
        />

        <Pagination
          v-if="store.pagination.lastPage > 1"
          :meta="store.pagination"
          @change="handlePageChange"
        />
      </main>
    </div>
  </div>
</template>
