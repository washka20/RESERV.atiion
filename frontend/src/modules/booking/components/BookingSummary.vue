<script setup lang="ts">
import { computed } from 'vue'
import BaseCard from '@/shared/components/base/BaseCard.vue'
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
  <section data-test-id="booking-summary">
    <BaseCard as="section" padding="md" elevation="none">
      <h3 class="text-sm font-semibold uppercase tracking-wide text-text-subtle">Итого</h3>
      <dl class="mt-3 flex flex-col gap-2 text-sm">
        <div class="flex items-center justify-between">
          <dt class="text-text-subtle">Услуга</dt>
          <dd class="font-medium text-text" data-test-id="booking-summary-service">
            {{ service.name }}
          </dd>
        </div>
        <div class="flex items-center justify-between">
          <dt class="text-text-subtle">Параметры</dt>
          <dd class="text-text" data-test-id="booking-summary-params">
            {{ selectedParams }}
          </dd>
        </div>
        <div class="flex items-center justify-between border-t border-border pt-2">
          <dt class="text-text-subtle">Стоимость</dt>
          <dd
            class="text-lg font-semibold text-text"
            data-test-id="booking-summary-total"
          >
            {{ formattedTotal }}
          </dd>
        </div>
      </dl>
    </BaseCard>
  </section>
</template>
