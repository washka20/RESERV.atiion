<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import BaseButton from '@/shared/components/base/BaseButton.vue'
import BaseTextarea from '@/shared/components/base/BaseTextarea.vue'
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
      <h2 class="text-sm font-semibold uppercase tracking-wide text-text-subtle">
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

    <BaseTextarea
      v-model="notes"
      label="Комментарий"
      placeholder="Дополнительные пожелания"
      :rows="3"
      test-id="booking-notes-input"
    />

    <BookingSummary
      :service="service"
      :slot-id="selectedSlotId"
      :range="selectedRange"
      :quantity="selectedQuantity"
    />

    <div
      v-if="booking.error"
      class="rounded-md border border-danger/30 bg-danger/10 p-3 text-sm text-danger"
      data-test-id="booking-error"
    >
      {{ booking.error }}
    </div>

    <BaseButton
      type="submit"
      variant="primary"
      size="lg"
      full-width
      :disabled="!canSubmit"
      :loading="booking.isLoading"
      test-id="booking-submit-btn"
    >
      Забронировать
    </BaseButton>
  </form>
</template>
