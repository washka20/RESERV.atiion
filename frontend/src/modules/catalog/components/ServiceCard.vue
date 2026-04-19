<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import BaseBadge from '@/shared/components/base/BaseBadge.vue'
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
    class="flex flex-col overflow-hidden rounded-md border border-border bg-surface shadow-sm transition hover:shadow-md"
    data-test-id="catalog-service-card"
  >
    <div class="aspect-video w-full bg-surface-muted">
      <img
        v-if="imageUrl"
        :src="imageUrl"
        :alt="service.name"
        class="h-full w-full object-cover"
        loading="lazy"
      />
      <div
        v-else
        class="flex h-full w-full items-center justify-center text-sm text-text-subtle"
        aria-hidden="true"
      >
        Нет изображения
      </div>
    </div>

    <div class="flex flex-1 flex-col gap-2 p-4">
      <div class="flex items-start justify-between gap-2">
        <h3 class="text-base font-semibold text-text">
          {{ service.name }}
        </h3>
        <BaseBadge variant="info">{{ typeLabel }}</BaseBadge>
      </div>

      <p class="text-sm text-text-subtle">{{ service.categoryName }}</p>

      <div class="mt-auto flex items-center justify-between pt-3">
        <span class="text-lg font-bold text-text">{{ formattedPrice }}</span>
        <RouterLink
          :to="{ name: 'catalog-service', params: { id: service.id } }"
          class="text-sm font-medium text-accent hover:opacity-80"
          data-test-id="catalog-service-card-link"
        >
          Подробнее
        </RouterLink>
      </div>
    </div>
  </article>
</template>
