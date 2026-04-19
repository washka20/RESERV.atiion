<script setup lang="ts">
import { computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import BaseBadge from '@/shared/components/base/BaseBadge.vue'
import BaseButton from '@/shared/components/base/BaseButton.vue'
import BaseSkeleton from '@/shared/components/base/BaseSkeleton.vue'
import { useCatalogStore } from '@/stores/catalog.store'

const props = defineProps<{ id: string }>()

const store = useCatalogStore()
const router = useRouter()

onMounted(() => {
  void store.fetchService(props.id)
})

watch(
  () => props.id,
  (next) => {
    if (next) void store.fetchService(next)
  },
)

const service = computed(() => store.currentService)

const CURRENCY_LOCALE = { RUB: 'ru-RU', USD: 'en-US', EUR: 'de-DE' } as const

const formattedPrice = computed(() => {
  if (!service.value) return ''
  const value = service.value.priceAmount / 100
  return new Intl.NumberFormat(CURRENCY_LOCALE[service.value.priceCurrency], {
    style: 'currency',
    currency: service.value.priceCurrency,
    maximumFractionDigits: 2,
  }).format(value)
})

const typeLabel = computed(() => {
  if (!service.value) return ''
  return service.value.type === 'time_slot' ? 'Временной слот' : 'Количество'
})

function bookService(): void {
  if (!service.value) return
  void router.push({ name: 'booking-create', query: { serviceId: service.value.id } })
}

function buildImageUrl(path: string): string {
  return `/storage/${path}`
}
</script>

<template>
  <div
    class="mx-auto max-w-5xl px-4 py-6 sm:px-6 lg:px-8"
    data-test-id="catalog-service-detail"
  >
    <div
      v-if="store.error"
      class="rounded-md border border-danger/30 bg-danger/10 p-4 text-sm text-danger"
      data-test-id="service-detail-error"
    >
      {{ store.error }}
    </div>

    <div
      v-else-if="store.isLoading || !service"
      class="flex flex-col gap-4"
      data-test-id="service-detail-loading"
    >
      <BaseSkeleton variant="custom" width="50%" height="2rem" />
      <BaseSkeleton variant="card" />
      <BaseSkeleton variant="text" :lines="2" />
    </div>

    <article v-else class="space-y-6">
      <header class="space-y-2">
        <p class="text-sm text-text-subtle">
          {{ service.categoryName }}
          <template v-if="service.subcategoryName">
            / {{ service.subcategoryName }}
          </template>
        </p>
        <h1 class="text-3xl font-bold text-text">{{ service.name }}</h1>
        <BaseBadge variant="info">{{ typeLabel }}</BaseBadge>
      </header>

      <section
        v-if="service.images.length > 0"
        class="grid grid-cols-1 gap-3 sm:grid-cols-2"
        data-test-id="service-detail-gallery"
      >
        <img
          v-for="(image, idx) in service.images"
          :key="image"
          :src="buildImageUrl(image)"
          :alt="`${service.name} — изображение ${idx + 1}`"
          class="aspect-video w-full rounded-md object-cover"
          loading="lazy"
        />
      </section>
      <div
        v-else
        class="flex aspect-video w-full items-center justify-center rounded-md bg-surface-muted text-sm text-text-subtle"
      >
        Нет изображений
      </div>

      <section class="space-y-2">
        <h2 class="text-lg font-semibold text-text">Описание</h2>
        <p class="whitespace-pre-line text-text">{{ service.description }}</p>
      </section>

      <section
        class="grid grid-cols-1 gap-3 rounded-md border border-border bg-surface-muted p-4 sm:grid-cols-3"
      >
        <div>
          <dt class="text-xs uppercase tracking-wide text-text-subtle">Цена</dt>
          <dd class="text-lg font-semibold text-text">{{ formattedPrice }}</dd>
        </div>
        <div v-if="service.durationMinutes !== null">
          <dt class="text-xs uppercase tracking-wide text-text-subtle">Длительность</dt>
          <dd class="text-lg font-semibold text-text">
            {{ service.durationMinutes }} мин
          </dd>
        </div>
        <div v-if="service.totalQuantity !== null">
          <dt class="text-xs uppercase tracking-wide text-text-subtle">Доступно</dt>
          <dd class="text-lg font-semibold text-text">
            {{ service.totalQuantity }} шт
          </dd>
        </div>
      </section>

      <div class="flex justify-end">
        <BaseButton
          variant="primary"
          size="lg"
          test-id="service-detail-book-btn"
          @click="bookService"
        >
          Забронировать
        </BaseButton>
      </div>
    </article>
  </div>
</template>
