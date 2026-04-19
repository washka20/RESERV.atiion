<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import BaseInput from '@/shared/components/base/BaseInput.vue'
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

function onQuantityUpdate(raw: string): void {
  const parsed = Number(raw)
  quantityInput.value = Number.isFinite(parsed) ? parsed : 1
}
</script>

<template>
  <div class="space-y-3" data-test-id="booking-quantity-picker">
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
      <BaseInput
        v-model="checkIn"
        label="Дата заезда"
        type="date"
        test-id="booking-date-checkin-input"
      />
      <BaseInput
        v-model="checkOut"
        label="Дата выезда"
        type="date"
        test-id="booking-date-checkout-input"
      />
    </div>

    <BaseInput
      :model-value="quantityInput"
      label="Количество"
      type="number"
      test-id="booking-quantity-input"
      :input-attrs="{ min: 1, max: total }"
      @update:model-value="onQuantityUpdate"
    />

    <p
      v-if="quantityAvailability"
      class="text-sm"
      :class="quantityAvailability.available ? 'text-text-subtle' : 'text-danger'"
      data-test-id="booking-availability-info"
    >
      {{ quantityAvailability.availableQuantity }} доступно из {{ quantityAvailability.total }}
    </p>
  </div>
</template>
