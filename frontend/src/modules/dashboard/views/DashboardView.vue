<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { useBookingStore } from '@/stores/booking.store'
import BookingFilters from '../components/BookingFilters.vue'
import BookingsList from '../components/BookingsList.vue'

const booking = useBookingStore()

/** Значение 'all' — сбрасывает фильтр (не передаётся в API). */
const filterStatus = ref<string>('all')

async function load(): Promise<void> {
  const params = filterStatus.value === 'all' ? {} : { status: filterStatus.value }
  try {
    await booking.fetchUserBookings(params)
  } catch {
    /* ошибка уже в booking.error */
  }
}

onMounted(load)

watch(filterStatus, load)

async function handleCancel(id: string): Promise<void> {
  try {
    await booking.cancelBooking(id)
  } catch {
    /* ошибка уже в booking.error */
  }
}
</script>

<template>
  <div
    class="mx-auto max-w-5xl px-4 py-6 sm:px-6 lg:px-8"
    data-test-id="dashboard-page"
  >
    <header class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Мои бронирования</h1>
        <p class="mt-1 text-sm text-gray-500">Все ваши бронирования в одном месте</p>
      </div>
      <BookingFilters v-model="filterStatus" />
    </header>

    <div
      v-if="booking.error"
      class="mb-4 rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700"
      data-test-id="dashboard-error"
    >
      {{ booking.error }}
    </div>

    <div
      v-if="booking.isLoading"
      class="rounded-md border border-gray-200 bg-gray-50 p-8 text-center text-sm text-gray-500"
      data-test-id="dashboard-loading"
    >
      Загрузка…
    </div>

    <div
      v-else-if="booking.userBookings.length === 0"
      class="rounded-md border border-gray-200 bg-gray-50 p-8 text-center text-sm text-gray-500"
      data-test-id="dashboard-empty"
    >
      У вас пока нет бронирований
    </div>

    <BookingsList
      v-else
      :bookings="booking.userBookings"
      @cancel="handleCancel"
    />
  </div>
</template>
