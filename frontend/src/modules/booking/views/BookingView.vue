<script setup lang="ts">
import { computed, onMounted, watch } from 'vue'
import { useCatalogStore } from '@/stores/catalog.store'
import BookingForm from '../components/BookingForm.vue'

const props = defineProps<{ serviceId: string }>()

const catalog = useCatalogStore()

onMounted(() => {
  void catalog.fetchService(props.serviceId)
})

watch(
  () => props.serviceId,
  (next) => {
    if (next) void catalog.fetchService(next)
  },
)

const service = computed(() => catalog.currentService)
</script>

<template>
  <div
    class="mx-auto max-w-3xl px-4 py-6 sm:px-6 lg:px-8"
    data-test-id="booking-page"
  >
    <div
      v-if="catalog.error"
      class="rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-700"
      data-test-id="booking-page-error"
    >
      {{ catalog.error }}
    </div>

    <div
      v-else-if="catalog.isLoading && !service"
      class="space-y-4"
      data-test-id="booking-page-loading"
    >
      <div class="h-8 w-1/2 animate-pulse rounded bg-gray-200" />
      <div class="h-24 w-full animate-pulse rounded bg-gray-200" />
      <div class="h-40 w-full animate-pulse rounded bg-gray-200" />
    </div>

    <div
      v-else-if="!service"
      class="rounded-md border border-gray-200 bg-gray-50 p-8 text-center text-sm text-gray-500"
      data-test-id="booking-service-not-found"
    >
      Service not found
    </div>

    <section v-else class="space-y-6">
      <header class="space-y-1">
        <h1 class="text-2xl font-bold text-gray-900" data-test-id="booking-service-name">
          {{ service.name }}
        </h1>
        <p class="text-sm text-gray-500">
          {{ service.categoryName }}
          <template v-if="service.subcategoryName"> / {{ service.subcategoryName }} </template>
        </p>
      </header>

      <BookingForm :service="service" />
    </section>
  </div>
</template>
