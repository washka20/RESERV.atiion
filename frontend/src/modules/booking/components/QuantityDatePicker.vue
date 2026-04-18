<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useBookingStore } from '@/stores/booking.store'

const props = defineProps<{
  serviceId: string
  total: number
  range: { checkIn: string; checkOut: string } | null
  quantity: number
}>()

const emit = defineEmits<{
  'update:range': [value: { checkIn: string; checkOut: string } | null]
  'update:quantity': [value: number]
}>()

const booking = useBookingStore()

const checkIn = ref<string>(props.range?.checkIn ?? '')
const checkOut = ref<string>(props.range?.checkOut ?? '')
const quantityInput = ref<number>(props.quantity)

const quantityAvailability = computed(() => {
  const av = booking.availability
  return av && av.type === 'quantity' ? av : null
})

function emitRange(): void {
  if (checkIn.value && checkOut.value && checkIn.value < checkOut.value) {
    emit('update:range', { checkIn: checkIn.value, checkOut: checkOut.value })
  } else {
    emit('update:range', null)
  }
}

async function refreshAvailability(): Promise<void> {
  if (
    !checkIn.value ||
    !checkOut.value ||
    checkIn.value >= checkOut.value ||
    quantityInput.value < 1
  ) {
    return
  }
  try {
    await booking.checkAvailability(props.serviceId, {
      type: 'quantity',
      checkIn: checkIn.value,
      checkOut: checkOut.value,
      requested: quantityInput.value,
    })
  } catch {
    /* ошибка уже в booking.error */
  }
}

watch(checkIn, () => {
  emitRange()
  void refreshAvailability()
})

watch(checkOut, () => {
  emitRange()
  void refreshAvailability()
})

watch(quantityInput, (next) => {
  const normalized = Number.isFinite(next) && next >= 1 ? Math.floor(next) : 1
  if (normalized !== next) {
    quantityInput.value = normalized
    return
  }
  emit('update:quantity', normalized)
  void refreshAvailability()
})
</script>

<template>
  <div class="space-y-3" data-test-id="booking-quantity-picker">
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
      <div>
        <label for="booking-check-in" class="block text-sm font-medium text-gray-700">
          Дата заезда
        </label>
        <input
          id="booking-check-in"
          v-model="checkIn"
          type="date"
          class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          data-test-id="booking-date-checkin-input"
        />
      </div>
      <div>
        <label for="booking-check-out" class="block text-sm font-medium text-gray-700">
          Дата выезда
        </label>
        <input
          id="booking-check-out"
          v-model="checkOut"
          type="date"
          class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          data-test-id="booking-date-checkout-input"
        />
      </div>
    </div>

    <div>
      <label for="booking-quantity" class="block text-sm font-medium text-gray-700">
        Количество
      </label>
      <input
        id="booking-quantity"
        v-model.number="quantityInput"
        type="number"
        min="1"
        :max="total"
        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
        data-test-id="booking-quantity-input"
      />
    </div>

    <p
      v-if="quantityAvailability"
      class="text-sm"
      :class="quantityAvailability.available ? 'text-gray-600' : 'text-red-600'"
      data-test-id="booking-availability-info"
    >
      {{ quantityAvailability.availableQuantity }} доступно из {{ quantityAvailability.total }}
    </p>
  </div>
</template>
