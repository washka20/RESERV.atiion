<script setup lang="ts">
import { computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
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
      class="rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-700"
      data-test-id="service-detail-error"
    >
      {{ store.error }}
    </div>

    <div
      v-else-if="store.isLoading || !service"
      class="space-y-4"
      data-test-id="service-detail-loading"
    >
      <div class="h-8 w-1/2 animate-pulse rounded bg-gray-200" />
      <div class="aspect-video w-full animate-pulse rounded-lg bg-gray-200" />
      <div class="h-4 w-3/4 animate-pulse rounded bg-gray-200" />
      <div class="h-4 w-2/3 animate-pulse rounded bg-gray-200" />
    </div>

    <article v-else class="space-y-6">
      <header class="space-y-2">
        <p class="text-sm text-gray-500">
          {{ service.categoryName }}
          <template v-if="service.subcategoryName">
            / {{ service.subcategoryName }}
          </template>
        </p>
        <h1 class="text-3xl font-bold text-gray-900">{{ service.name }}</h1>
        <span
          class="inline-block rounded-full bg-indigo-100 px-3 py-1 text-xs font-medium text-indigo-700"
        >
          {{ typeLabel }}
        </span>
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
          class="aspect-video w-full rounded-lg object-cover"
          loading="lazy"
        />
      </section>
      <div
        v-else
        class="flex aspect-video w-full items-center justify-center rounded-lg bg-gray-100 text-sm text-gray-400"
      >
        Нет изображений
      </div>

      <section class="space-y-2">
        <h2 class="text-lg font-semibold text-gray-900">Описание</h2>
        <p class="whitespace-pre-line text-gray-700">{{ service.description }}</p>
      </section>

      <section
        class="grid grid-cols-1 gap-3 rounded-lg border border-gray-200 bg-gray-50 p-4 sm:grid-cols-3"
      >
        <div>
          <dt class="text-xs uppercase tracking-wide text-gray-500">Цена</dt>
          <dd class="text-lg font-semibold text-gray-900">{{ formattedPrice }}</dd>
        </div>
        <div v-if="service.durationMinutes !== null">
          <dt class="text-xs uppercase tracking-wide text-gray-500">Длительность</dt>
          <dd class="text-lg font-semibold text-gray-900">
            {{ service.durationMinutes }} мин
          </dd>
        </div>
        <div v-if="service.totalQuantity !== null">
          <dt class="text-xs uppercase tracking-wide text-gray-500">Доступно</dt>
          <dd class="text-lg font-semibold text-gray-900">
            {{ service.totalQuantity }} шт
          </dd>
        </div>
      </section>

      <div class="flex justify-end">
        <button
          type="button"
          class="rounded-md bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
          data-test-id="service-detail-book-btn"
          @click="bookService"
        >
          Забронировать
        </button>
      </div>
    </article>
  </div>
</template>
