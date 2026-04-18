<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import type { Service } from '@/types/catalog.types'
import type { CreateBookingPayload } from '@/types/booking.types'
import { useBookingStore } from '@/stores/booking.store'
import TimeSlotPicker from './TimeSlotPicker.vue'
import QuantityDatePicker from './QuantityDatePicker.vue'
import BookingSummary from './BookingSummary.vue'

const props = defineProps<{ service: Service }>()

const booking = useBookingStore()
const router = useRouter()

const selectedSlotId = ref<string | null>(null)
const selectedRange = ref<{ checkIn: string; checkOut: string } | null>(null)
const selectedQuantity = ref<number>(1)
const notes = ref<string>('')

/**
 * Готовность к отправке:
 * - time_slot: выбран slot
 * - quantity: выбран диапазон дат, quantity >= 1, backend подтвердил доступность
 */
const canSubmit = computed(() => {
  if (props.service.type === 'time_slot') {
    return selectedSlotId.value !== null
  }

  if (!selectedRange.value || selectedQuantity.value < 1) {
    return false
  }

  const av = booking.availability
  if (av && av.type === 'quantity') {
    return av.available
  }
  return false
})

async function submit(): Promise<void> {
  if (!canSubmit.value || booking.isLoading) return

  const payload: CreateBookingPayload =
    props.service.type === 'time_slot'
      ? {
          serviceId: props.service.id,
          type: 'time_slot',
          slotId: selectedSlotId.value ?? undefined,
          notes: notes.value || undefined,
        }
      : {
          serviceId: props.service.id,
          type: 'quantity',
          checkIn: selectedRange.value?.checkIn,
          checkOut: selectedRange.value?.checkOut,
          quantity: selectedQuantity.value,
          notes: notes.value || undefined,
        }

  try {
    const created = await booking.createBooking(payload)
    await router.push({ name: 'booking-confirm', params: { id: created.id } })
  } catch {
    /* ошибка уже в booking.error */
  }
}
</script>

<template>
  <form class="space-y-6" data-test-id="booking-form" @submit.prevent="submit">
    <section class="space-y-3">
      <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">
        Выберите параметры
      </h2>

      <TimeSlotPicker
        v-if="service.type === 'time_slot'"
        :service-id="service.id"
        :model-value="selectedSlotId"
        @update:model-value="selectedSlotId = $event"
      />

      <QuantityDatePicker
        v-else
        :service-id="service.id"
        :total="service.totalQuantity ?? 0"
        :range="selectedRange"
        :quantity="selectedQuantity"
        @update:range="selectedRange = $event"
        @update:quantity="selectedQuantity = $event"
      />
    </section>

    <section class="space-y-2">
      <label for="booking-notes" class="block text-sm font-medium text-gray-700">
        Комментарий
      </label>
      <textarea
        id="booking-notes"
        v-model="notes"
        rows="3"
        class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
        placeholder="Дополнительные пожелания"
        data-test-id="booking-notes-input"
      />
    </section>

    <BookingSummary
      :service="service"
      :slot-id="selectedSlotId"
      :range="selectedRange"
      :quantity="selectedQuantity"
    />

    <div
      v-if="booking.error"
      class="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700"
      data-test-id="booking-error"
    >
      {{ booking.error }}
    </div>

    <button
      type="submit"
      class="w-full rounded-md bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
      :disabled="!canSubmit || booking.isLoading"
      data-test-id="booking-submit-btn"
    >
      {{ booking.isLoading ? 'Отправка…' : 'Забронировать' }}
    </button>
  </form>
</template>
