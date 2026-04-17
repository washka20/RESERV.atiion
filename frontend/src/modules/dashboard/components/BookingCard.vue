<script setup lang="ts">
import { computed } from 'vue'
import type { Booking, BookingStatus } from '@/types/booking.types'
import type { Currency } from '@/types/catalog.types'

const props = defineProps<{ booking: Booking }>()
const emit = defineEmits<{ cancel: [id: string] }>()

const CURRENCY_LOCALE: Record<string, string> = {
  RUB: 'ru-RU',
  USD: 'en-US',
  EUR: 'de-DE',
}

const STATUS_LABEL: Record<BookingStatus, string> = {
  pending: 'В обработке',
  confirmed: 'Подтверждено',
  cancelled: 'Отменено',
  completed: 'Выполнено',
}

const STATUS_CLASS: Record<BookingStatus, string> = {
  pending: 'bg-yellow-100 text-yellow-800',
  confirmed: 'bg-green-100 text-green-800',
  cancelled: 'bg-red-100 text-red-800',
  completed: 'bg-blue-100 text-blue-800',
}

const statusLabel = computed(() => STATUS_LABEL[props.booking.status])
const statusClass = computed(() => STATUS_CLASS[props.booking.status])

const formattedTotal = computed(() => {
  const currency = props.booking.totalPriceCurrency as Currency
  const locale = CURRENCY_LOCALE[currency] ?? 'ru-RU'
  return new Intl.NumberFormat(locale, {
    style: 'currency',
    currency,
    maximumFractionDigits: 2,
  }).format(props.booking.totalPriceAmount / 100)
})

const canCancel = computed(
  () => props.booking.status === 'pending' || props.booking.status === 'confirmed',
)

/** Короткое отображение serviceId (первые 8 символов) пока нет service_name на backend. */
const shortServiceId = computed(() => props.booking.serviceId.slice(0, 8))

const dateLine = computed(() => {
  if (props.booking.type === 'time_slot') {
    const start = props.booking.startAt ? new Date(props.booking.startAt).toLocaleString() : '—'
    const end = props.booking.endAt ? new Date(props.booking.endAt).toLocaleString() : '—'
    return `${start} — ${end}`
  }
  const checkIn = props.booking.checkIn ?? '—'
  const checkOut = props.booking.checkOut ?? '—'
  const qty = props.booking.quantity ?? 0
  return `${checkIn} → ${checkOut}, ${qty} шт`
})

function onCancel(): void {
  emit('cancel', props.booking.id)
}
</script>

<template>
  <article
    class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm"
    data-test-id="dashboard-booking-card"
    :data-booking-id="booking.id"
  >
    <header class="flex items-start justify-between gap-3">
      <div>
        <h3 class="text-base font-semibold text-gray-900" data-test-id="dashboard-booking-service">
          {{ shortServiceId }}…
        </h3>
        <p class="mt-0.5 text-xs text-gray-500">
          Создано {{ new Date(booking.createdAt).toLocaleString() }}
        </p>
      </div>
      <span
        class="shrink-0 rounded-full px-3 py-1 text-xs font-medium"
        :class="statusClass"
        data-test-id="dashboard-booking-status"
      >
        {{ statusLabel }}
      </span>
    </header>

    <p class="mt-3 text-sm text-gray-700" data-test-id="dashboard-booking-dates">
      {{ dateLine }}
    </p>

    <footer class="mt-4 flex items-center justify-between">
      <span class="text-lg font-semibold text-gray-900" data-test-id="dashboard-booking-total">
        {{ formattedTotal }}
      </span>
      <button
        v-if="canCancel"
        type="button"
        class="rounded-md border border-red-300 bg-white px-3 py-1.5 text-sm font-medium text-red-700 hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
        data-test-id="dashboard-booking-cancel-btn"
        @click="onCancel"
      >
        Отменить
      </button>
    </footer>
  </article>
</template>
