<script setup lang="ts">
import { computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useBookingStore } from '@/stores/booking.store'
import { useCatalogStore } from '@/stores/catalog.store'
import type { BookingStatus } from '@/types/booking.types'
import type { Currency } from '@/types/catalog.types'

const props = defineProps<{ id: string }>()

const booking = useBookingStore()
const catalog = useCatalogStore()
const router = useRouter()

const CURRENCY_LOCALE: Record<string, string> = {
  RUB: 'ru-RU',
  USD: 'en-US',
  EUR: 'de-DE',
}

const STATUS_LABEL: Record<BookingStatus, string> = {
  pending: 'в обработке',
  confirmed: 'подтверждено',
  cancelled: 'отменено',
  completed: 'выполнено',
}

const STATUS_CLASS: Record<BookingStatus, string> = {
  pending: 'bg-yellow-100 text-yellow-800',
  confirmed: 'bg-green-100 text-green-800',
  cancelled: 'bg-red-100 text-red-800',
  completed: 'bg-blue-100 text-blue-800',
}

onMounted(async () => {
  await booking.fetchBooking(props.id)
  if (booking.currentBooking) {
    await catalog.fetchService(booking.currentBooking.serviceId)
  }
})

watch(
  () => props.id,
  async (next) => {
    if (!next) return
    await booking.fetchBooking(next)
    if (booking.currentBooking) {
      await catalog.fetchService(booking.currentBooking.serviceId)
    }
  },
)

const current = computed(() => booking.currentBooking)
const service = computed(() => catalog.currentService)

const statusLabel = computed(() =>
  current.value ? STATUS_LABEL[current.value.status] : '',
)

const statusClass = computed(() =>
  current.value ? STATUS_CLASS[current.value.status] : '',
)

const formattedTotal = computed(() => {
  if (!current.value) return ''
  const currency = current.value.totalPriceCurrency as Currency
  const locale = CURRENCY_LOCALE[currency] ?? 'ru-RU'
  return new Intl.NumberFormat(locale, {
    style: 'currency',
    currency,
    maximumFractionDigits: 2,
  }).format(current.value.totalPriceAmount / 100)
})

function goToDashboard(): void {
  void router.push({ name: 'dashboard' })
}

function formatDateTime(iso: string): string {
  return new Date(iso).toLocaleString()
}
</script>

<template>
  <div class="mx-auto max-w-2xl px-4 py-6 sm:px-6 lg:px-8" data-test-id="booking-confirm-page">
    <div
      v-if="booking.error"
      class="rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-700"
      data-test-id="booking-confirm-error"
    >
      {{ booking.error }}
    </div>

    <div
      v-else-if="booking.isLoading || !current"
      class="space-y-4"
      data-test-id="booking-confirm-loading"
    >
      <div class="h-8 w-1/2 animate-pulse rounded bg-gray-200" />
      <div class="h-32 w-full animate-pulse rounded bg-gray-200" />
    </div>

    <article
      v-else
      class="space-y-6 rounded-lg border border-gray-200 bg-white p-6 shadow-sm"
      data-test-id="booking-confirm-card"
    >
      <header class="space-y-2 text-center">
        <h1 class="text-2xl font-bold text-gray-900">
          Бронирование
          <span
            class="ml-2 inline-block rounded-full px-3 py-1 text-sm font-medium"
            :class="statusClass"
            data-test-id="booking-confirm-status"
          >
            {{ statusLabel }}
          </span>
        </h1>
        <p class="text-sm text-gray-500" data-test-id="booking-confirm-id">
          ID: <code class="rounded bg-gray-100 px-1.5 py-0.5">{{ current.id }}</code>
        </p>
      </header>

      <dl class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
        <div>
          <dt class="text-xs uppercase tracking-wide text-gray-500">Услуга</dt>
          <dd
            class="mt-1 text-base font-medium text-gray-900"
            data-test-id="booking-confirm-service"
          >
            {{ service?.name ?? current.serviceId }}
          </dd>
        </div>

        <div>
          <dt class="text-xs uppercase tracking-wide text-gray-500">Стоимость</dt>
          <dd
            class="mt-1 text-base font-semibold text-gray-900"
            data-test-id="booking-confirm-total"
          >
            {{ formattedTotal }}
          </dd>
        </div>

        <template v-if="current.type === 'time_slot'">
          <div>
            <dt class="text-xs uppercase tracking-wide text-gray-500">Начало</dt>
            <dd class="mt-1 text-gray-900" data-test-id="booking-confirm-start">
              {{ current.startAt ? formatDateTime(current.startAt) : '—' }}
            </dd>
          </div>
          <div>
            <dt class="text-xs uppercase tracking-wide text-gray-500">Окончание</dt>
            <dd class="mt-1 text-gray-900" data-test-id="booking-confirm-end">
              {{ current.endAt ? formatDateTime(current.endAt) : '—' }}
            </dd>
          </div>
        </template>

        <template v-else>
          <div>
            <dt class="text-xs uppercase tracking-wide text-gray-500">Заезд</dt>
            <dd class="mt-1 text-gray-900" data-test-id="booking-confirm-check-in">
              {{ current.checkIn ?? '—' }}
            </dd>
          </div>
          <div>
            <dt class="text-xs uppercase tracking-wide text-gray-500">Выезд</dt>
            <dd class="mt-1 text-gray-900" data-test-id="booking-confirm-check-out">
              {{ current.checkOut ?? '—' }}
            </dd>
          </div>
          <div>
            <dt class="text-xs uppercase tracking-wide text-gray-500">Количество</dt>
            <dd class="mt-1 text-gray-900" data-test-id="booking-confirm-quantity">
              {{ current.quantity ?? '—' }}
            </dd>
          </div>
        </template>

        <div v-if="current.notes" class="sm:col-span-2">
          <dt class="text-xs uppercase tracking-wide text-gray-500">Комментарий</dt>
          <dd class="mt-1 whitespace-pre-line text-gray-700" data-test-id="booking-confirm-notes">
            {{ current.notes }}
          </dd>
        </div>
      </dl>

      <div class="flex justify-end">
        <button
          type="button"
          class="rounded-md bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
          data-test-id="booking-confirm-dashboard-btn"
          @click="goToDashboard"
        >
          В кабинет
        </button>
      </div>
    </article>
  </div>
</template>
