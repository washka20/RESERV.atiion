<script setup lang="ts">
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
      class="rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-700"
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
        class="overflow-hidden rounded-lg border border-gray-200 bg-white"
      >
        <div class="aspect-video w-full animate-pulse bg-gray-200" />
        <div class="space-y-3 p-4">
          <div class="h-4 w-3/4 animate-pulse rounded bg-gray-200" />
          <div class="h-3 w-1/2 animate-pulse rounded bg-gray-200" />
          <div class="h-5 w-1/3 animate-pulse rounded bg-gray-200" />
        </div>
      </div>
    </div>

    <div
      v-else-if="services.length === 0"
      class="rounded-md border border-gray-200 bg-gray-50 p-8 text-center text-sm text-gray-500"
      data-test-id="catalog-service-list-empty"
    >
      Услуги не найдены
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
