<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useBookingStore } from '@/stores/booking.store'

const props = defineProps<{
  serviceId: string
  modelValue: string | null
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string | null]
}>()

const booking = useBookingStore()

const selectedDate = ref<string>('')

const slots = computed(() => {
  const av = booking.availability
  if (!av || av.type !== 'time_slot') return []
  return av.slots
})

/** Форматирует ISO `startAt` в часы:минуты по локальному времени. */
function formatTime(isoString: string): string {
  const date = new Date(isoString)
  return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}

watch(selectedDate, async (next) => {
  emit('update:modelValue', null)
  if (!next) return
  try {
    await booking.checkAvailability(props.serviceId, { type: 'time_slot', date: next })
  } catch {
    /* ошибка уже в booking.error */
  }
})

function selectSlot(id: string): void {
  emit('update:modelValue', id)
}
</script>

<template>
  <div class="space-y-3" data-test-id="booking-time-slot-picker">
    <div>
      <label for="booking-date" class="block text-sm font-medium text-gray-700">
        Дата
      </label>
      <input
        id="booking-date"
        v-model="selectedDate"
        type="date"
        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
        data-test-id="booking-date-input"
      />
    </div>

    <p
      v-if="!selectedDate"
      class="text-sm text-gray-500"
      data-test-id="booking-choose-date"
    >
      Выберите дату
    </p>

    <div
      v-else-if="booking.isLoading"
      class="text-sm text-gray-500"
      data-test-id="booking-slots-loading"
    >
      Загрузка слотов…
    </div>

    <p
      v-else-if="slots.length === 0"
      class="rounded-md border border-gray-200 bg-gray-50 p-3 text-sm text-gray-500"
      data-test-id="booking-no-slots"
    >
      Нет доступных слотов
    </p>

    <div
      v-else
      class="grid grid-cols-2 gap-2 sm:grid-cols-3 md:grid-cols-4"
      data-test-id="booking-slots-grid"
    >
      <button
        v-for="slot in slots"
        :key="slot.id"
        type="button"
        class="rounded-md border px-3 py-2 text-sm font-medium transition"
        :class="
          modelValue === slot.id
            ? 'border-indigo-600 bg-indigo-50 text-indigo-700'
            : 'border-gray-300 bg-white text-gray-700 hover:border-indigo-400 hover:bg-indigo-50/40'
        "
        data-test-id="booking-slot-btn"
        :data-slot-id="slot.id"
        @click="selectSlot(slot.id)"
      >
        {{ formatTime(slot.startAt) }}
      </button>
    </div>
  </div>
</template>
