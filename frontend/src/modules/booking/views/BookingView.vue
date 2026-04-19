<script setup lang="ts">
import { computed, onMounted, watch } from 'vue'
import BaseEmptyState from '@/shared/components/base/BaseEmptyState.vue'
import BaseSkeleton from '@/shared/components/base/BaseSkeleton.vue'
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
      class="rounded-md border border-danger/30 bg-danger/10 p-4 text-sm text-danger"
      data-test-id="booking-page-error"
    >
      {{ catalog.error }}
    </div>

    <div
      v-else-if="catalog.isLoading && !service"
      class="flex flex-col gap-4"
      data-test-id="booking-page-loading"
    >
      <BaseSkeleton variant="custom" width="50%" height="2rem" />
      <BaseSkeleton variant="custom" width="100%" height="6rem" />
      <BaseSkeleton variant="custom" width="100%" height="10rem" />
    </div>

    <div v-else-if="!service" data-test-id="booking-service-not-found">
      <BaseEmptyState
        title="Service not found"
        description="Услуга не найдена или недоступна"
      />
    </div>

    <section v-else class="space-y-6">
      <header class="space-y-1">
        <h1 class="text-2xl font-bold text-text" data-test-id="booking-service-name">
          {{ service.name }}
        </h1>
        <p class="text-sm text-text-subtle">
          {{ service.categoryName }}
          <template v-if="service.subcategoryName"> / {{ service.subcategoryName }} </template>
        </p>
      </header>

      <BookingForm :service="service" />
    </section>
  </div>
</template>
