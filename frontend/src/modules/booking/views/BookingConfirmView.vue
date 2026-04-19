<script setup lang="ts">
import { computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import BaseBadge from '@/shared/components/base/BaseBadge.vue'
import BaseButton from '@/shared/components/base/BaseButton.vue'
import BaseSkeleton from '@/shared/components/base/BaseSkeleton.vue'
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

type BadgeVariant = 'neutral' | 'success' | 'warning' | 'danger' | 'info'

const STATUS_VARIANT: Record<BookingStatus, BadgeVariant> = {
  pending: 'warning',
  confirmed: 'success',
  cancelled: 'danger',
  completed: 'info',
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

const statusVariant = computed<BadgeVariant>(() =>
  current.value ? STATUS_VARIANT[current.value.status] : 'neutral',
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
      class="rounded-md border border-danger/30 bg-danger/10 p-4 text-sm text-danger"
      data-test-id="booking-confirm-error"
    >
      {{ booking.error }}
    </div>

    <div
      v-else-if="booking.isLoading || !current"
      class="flex flex-col gap-4"
      data-test-id="booking-confirm-loading"
    >
      <BaseSkeleton variant="custom" width="50%" height="2rem" />
      <BaseSkeleton variant="custom" width="100%" height="8rem" />
    </div>

    <article
      v-else
      class="space-y-6 rounded-md border border-border bg-surface p-6 shadow-sm"
      data-test-id="booking-confirm-card"
    >
      <header class="space-y-2 text-center">
        <h1 class="text-2xl font-bold text-text">
          Бронирование
          <span class="ml-2 inline-block align-middle" data-test-id="booking-confirm-status">
            <BaseBadge :variant="statusVariant">{{ statusLabel }}</BaseBadge>
          </span>
        </h1>
        <p class="text-sm text-text-subtle" data-test-id="booking-confirm-id">
          ID: <code class="rounded bg-surface-muted px-1.5 py-0.5">{{ current.id }}</code>
        </p>
      </header>

      <dl class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
        <div>
          <dt class="text-xs uppercase tracking-wide text-text-subtle">Услуга</dt>
          <dd
            class="mt-1 text-base font-medium text-text"
            data-test-id="booking-confirm-service"
          >
            {{ service?.name ?? current.serviceId }}
          </dd>
        </div>

        <div>
          <dt class="text-xs uppercase tracking-wide text-text-subtle">Стоимость</dt>
          <dd
            class="mt-1 text-base font-semibold text-text"
            data-test-id="booking-confirm-total"
          >
            {{ formattedTotal }}
          </dd>
        </div>

        <template v-if="current.type === 'time_slot'">
          <div>
            <dt class="text-xs uppercase tracking-wide text-text-subtle">Начало</dt>
            <dd class="mt-1 text-text" data-test-id="booking-confirm-start">
              {{ current.startAt ? formatDateTime(current.startAt) : '—' }}
            </dd>
          </div>
          <div>
            <dt class="text-xs uppercase tracking-wide text-text-subtle">Окончание</dt>
            <dd class="mt-1 text-text" data-test-id="booking-confirm-end">
              {{ current.endAt ? formatDateTime(current.endAt) : '—' }}
            </dd>
          </div>
        </template>

        <template v-else>
          <div>
            <dt class="text-xs uppercase tracking-wide text-text-subtle">Заезд</dt>
            <dd class="mt-1 text-text" data-test-id="booking-confirm-check-in">
              {{ current.checkIn ?? '—' }}
            </dd>
          </div>
          <div>
            <dt class="text-xs uppercase tracking-wide text-text-subtle">Выезд</dt>
            <dd class="mt-1 text-text" data-test-id="booking-confirm-check-out">
              {{ current.checkOut ?? '—' }}
            </dd>
          </div>
          <div>
            <dt class="text-xs uppercase tracking-wide text-text-subtle">Количество</dt>
            <dd class="mt-1 text-text" data-test-id="booking-confirm-quantity">
              {{ current.quantity ?? '—' }}
            </dd>
          </div>
        </template>

        <div v-if="current.notes" class="sm:col-span-2">
          <dt class="text-xs uppercase tracking-wide text-text-subtle">Комментарий</dt>
          <dd class="mt-1 whitespace-pre-line text-text" data-test-id="booking-confirm-notes">
            {{ current.notes }}
          </dd>
        </div>
      </dl>

      <div class="flex justify-end">
        <BaseButton
          variant="primary"
          size="lg"
          test-id="booking-confirm-dashboard-btn"
          @click="goToDashboard"
        >
          В кабинет
        </BaseButton>
      </div>
    </article>
  </div>
</template>
