<script setup lang="ts">
import BaseEmptyState from '@/shared/components/base/BaseEmptyState.vue'
import BaseSkeleton from '@/shared/components/base/BaseSkeleton.vue'
import type { ServiceListItem } from '@/types/catalog.types'
import ServiceCard from './ServiceCard.vue'

defineProps<{
  services: ServiceListItem[]
  isLoading: boolean
  error?: string | null
}>()

const SKELETON_COUNT = 6
</script>

<template>
  <div data-test-id="catalog-service-list">
    <div
      v-if="error"
      class="rounded-md border border-danger/30 bg-danger/10 p-4 text-sm text-danger"
      data-test-id="catalog-service-list-error"
    >
      {{ error }}
    </div>

    <div
      v-else-if="isLoading"
      class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3"
      data-test-id="catalog-service-list-loading"
    >
      <div
        v-for="n in SKELETON_COUNT"
        :key="n"
        class="overflow-hidden rounded-md border border-border bg-surface"
      >
        <BaseSkeleton variant="card" />
        <div class="space-y-3 p-4">
          <BaseSkeleton variant="text" :lines="2" />
          <BaseSkeleton variant="custom" width="33%" height="1.25rem" />
        </div>
      </div>
    </div>

    <div
      v-else-if="services.length === 0"
      data-test-id="catalog-service-list-empty"
    >
      <BaseEmptyState
        title="Услуги не найдены"
        description="Попробуйте сбросить фильтры или изменить запрос"
      />
    </div>

    <div v-else class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
      <ServiceCard
        v-for="service in services"
        :key="service.id"
        :service="service"
      />
    </div>
  </div>
</template>
