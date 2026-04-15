<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import type { ServiceListItem } from '@/types/catalog.types'

const props = defineProps<{ service: ServiceListItem }>()

const CURRENCY_LOCALE: Record<ServiceListItem['priceCurrency'], string> = {
  RUB: 'ru-RU',
  USD: 'en-US',
  EUR: 'de-DE',
}

const formattedPrice = computed(() => {
  const value = props.service.priceAmount / 100
  return new Intl.NumberFormat(CURRENCY_LOCALE[props.service.priceCurrency], {
    style: 'currency',
    currency: props.service.priceCurrency,
    maximumFractionDigits: 2,
  }).format(value)
})

const typeLabel = computed(() =>
  props.service.type === 'time_slot' ? 'Слот' : 'Количество',
)

const imageUrl = computed(() =>
  props.service.primaryImage ? `/storage/${props.service.primaryImage}` : null,
)
</script>

<template>
  <article
    class="flex flex-col overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm transition hover:shadow-md"
    data-test-id="catalog-service-card"
  >
    <div class="aspect-video w-full bg-gray-100">
      <img
        v-if="imageUrl"
        :src="imageUrl"
        :alt="service.name"
        class="h-full w-full object-cover"
        loading="lazy"
      />
      <div
        v-else
        class="flex h-full w-full items-center justify-center text-sm text-gray-400"
        aria-hidden="true"
      >
        Нет изображения
      </div>
    </div>

    <div class="flex flex-1 flex-col gap-2 p-4">
      <div class="flex items-start justify-between gap-2">
        <h3 class="text-base font-semibold text-gray-900">
          {{ service.name }}
        </h3>
        <span
          class="shrink-0 rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-700"
        >
          {{ typeLabel }}
        </span>
      </div>

      <p class="text-sm text-gray-500">{{ service.categoryName }}</p>

      <div class="mt-auto flex items-center justify-between pt-3">
        <span class="text-lg font-bold text-gray-900">{{ formattedPrice }}</span>
        <RouterLink
          :to="{ name: 'catalog-service', params: { id: service.id } }"
          class="text-sm font-medium text-indigo-600 hover:text-indigo-700"
          data-test-id="catalog-service-card-link"
        >
          Подробнее
        </RouterLink>
      </div>
    </div>
  </article>
</template>
