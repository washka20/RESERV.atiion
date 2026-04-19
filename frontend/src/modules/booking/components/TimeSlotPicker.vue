<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import BaseInput from '@/shared/components/base/BaseInput.vue'
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
    <BaseInput
      v-model="selectedDate"
      label="Дата"
      type="date"
      test-id="booking-date-input"
    />

    <p
      v-if="!selectedDate"
      class="text-sm text-text-subtle"
      data-test-id="booking-choose-date"
    >
      Выберите дату
    </p>

    <div
      v-else-if="booking.isLoading"
      class="text-sm text-text-subtle"
      data-test-id="booking-slots-loading"
    >
      Загрузка слотов…
    </div>

    <p
      v-else-if="slots.length === 0"
      class="rounded-md border border-border bg-surface-muted p-3 text-sm text-text-subtle"
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
        class="rounded-md border px-3 py-2 text-sm font-medium transition-colors"
        :class="
          modelValue === slot.id
            ? 'border-accent bg-accent-soft text-accent'
            : 'border-border bg-surface text-text hover:border-accent'
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
