<script setup lang="ts">
import { computed } from 'vue'
import type { Currency, Service } from '@/types/catalog.types'

const props = defineProps<{
  service: Service
  slotId: string | null
  range: { checkIn: string; checkOut: string } | null
  quantity: number
}>()

const CURRENCY_LOCALE: Record<Currency, string> = {
  RUB: 'ru-RU',
  USD: 'en-US',
  EUR: 'de-DE',
}

/** Число ночей между checkIn и checkOut, минимум 1 при равных датах. */
const nights = computed(() => {
  if (!props.range) return 0
  const start = new Date(props.range.checkIn)
  const end = new Date(props.range.checkOut)
  const diffMs = end.getTime() - start.getTime()
  const days = Math.round(diffMs / (1000 * 60 * 60 * 24))
  return days > 0 ? days : 0
})

const totalAmount = computed(() => {
  if (props.service.type === 'time_slot') {
    return props.service.priceAmount
  }
  return props.service.priceAmount * Math.max(props.quantity, 0) * nights.value
})

const formattedTotal = computed(() => {
  const value = totalAmount.value / 100
  return new Intl.NumberFormat(CURRENCY_LOCALE[props.service.priceCurrency], {
    style: 'currency',
    currency: props.service.priceCurrency,
    maximumFractionDigits: 2,
  }).format(value)
})

const selectedParams = computed(() => {
  if (props.service.type === 'time_slot') {
    return props.slotId ? 'Слот выбран' : 'Слот не выбран'
  }

  if (!props.range) return 'Диапазон дат не выбран'
  const { checkIn, checkOut } = props.range
  return `${checkIn} — ${checkOut}, ${props.quantity} шт`
})
</script>

<template>
  <section
    class="rounded-lg border border-gray-200 bg-gray-50 p-4"
    data-test-id="booking-summary"
  >
    <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Итого</h3>
    <dl class="mt-3 space-y-2 text-sm">
      <div class="flex items-center justify-between">
        <dt class="text-gray-500">Услуга</dt>
        <dd class="font-medium text-gray-900" data-test-id="booking-summary-service">
          {{ service.name }}
        </dd>
      </div>
      <div class="flex items-center justify-between">
        <dt class="text-gray-500">Параметры</dt>
        <dd class="text-gray-700" data-test-id="booking-summary-params">
          {{ selectedParams }}
        </dd>
      </div>
      <div class="flex items-center justify-between border-t border-gray-200 pt-2">
        <dt class="text-gray-500">Стоимость</dt>
        <dd
          class="text-lg font-semibold text-gray-900"
          data-test-id="booking-summary-total"
        >
          {{ formattedTotal }}
        </dd>
      </div>
    </dl>
  </section>
</template>
