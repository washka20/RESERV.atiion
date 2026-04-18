import { ref } from 'vue'
import { defineStore } from 'pinia'
import * as bookingApi from '@/api/booking.api'
import * as availabilityApi from '@/api/availability.api'
import type {
  Availability,
  AvailabilityParams,
  Booking,
  BookingStatus,
  CreateBookingPayload,
} from '@/types/booking.types'

/** Параметры загрузки списка бронирований пользователя. */
export interface FetchUserBookingsParams {
  status?: BookingStatus | string
  page?: number
  perPage?: number
}

/**
 * Pinia store модуля бронирования.
 *
 * Управляет списком бронирований пользователя, активным бронированием,
 * результатом проверки доступности, флагами загрузки и ошибками.
 */
export const useBookingStore = defineStore('booking', () => {
  const userBookings = ref<Booking[]>([])
  const currentBooking = ref<Booking | null>(null)
  const availability = ref<Availability | null>(null)
  const total = ref(0)
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  function extractMessage(err: unknown, fallback: string): string {
    if (err instanceof Error) return err.message
    return fallback
  }

  /**
   * Загружает список бронирований текущего пользователя.
   *
   * При успехе записывает `userBookings` и `total` (из `meta.total` envelope).
   * При ошибке запоминает её в `error` и пробрасывает исключение вверх.
   */
  async function fetchUserBookings(params: FetchUserBookingsParams = {}): Promise<void> {
    isLoading.value = true
    error.value = null
    try {
      const envelope = await bookingApi.listBookings(params)
      if (envelope.success && envelope.data) {
        userBookings.value = envelope.data
        total.value = envelope.meta?.total ?? envelope.data.length
      } else {
        userBookings.value = []
        total.value = 0
      }
    } catch (err) {
      error.value = extractMessage(err, 'Failed to fetch bookings')
      throw err
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Загружает одно бронирование по id в `currentBooking`.
   */
  async function fetchBooking(id: string): Promise<void> {
    isLoading.value = true
    error.value = null
    try {
      const envelope = await bookingApi.getBooking(id)
      currentBooking.value = envelope.success ? envelope.data : null
    } catch (err) {
      error.value = extractMessage(err, 'Failed to fetch booking')
      currentBooking.value = null
      throw err
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Проверяет доступность услуги; результат — в `availability`.
   *
   * При ошибке — `availability = null`, `error` — сообщение.
   */
  async function checkAvailability(
    serviceId: string,
    params: AvailabilityParams,
  ): Promise<void> {
    isLoading.value = true
    error.value = null
    try {
      const envelope = await availabilityApi.checkAvailability(serviceId, params)
      availability.value = envelope.success ? envelope.data : null
    } catch (err) {
      error.value = extractMessage(err, 'Failed to check availability')
      availability.value = null
      throw err
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Создаёт бронирование. При успехе обновляет `currentBooking`.
   *
   * @throws Error с сообщением из envelope.error при `success=false`
   *         либо с сообщением исключения axios при сетевой/серверной ошибке.
   */
  async function createBooking(payload: CreateBookingPayload): Promise<Booking> {
    isLoading.value = true
    error.value = null
    try {
      const envelope = await bookingApi.createBooking(payload)
      if (!envelope.success || !envelope.data) {
        throw new Error(envelope.error?.message ?? 'Failed to create booking')
      }
      currentBooking.value = envelope.data
      return envelope.data
    } catch (err) {
      error.value = extractMessage(err, 'Failed to create booking')
      throw err
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Отменяет бронирование и обновляет его в `userBookings` (по id).
   */
  async function cancelBooking(id: string): Promise<void> {
    error.value = null
    try {
      const envelope = await bookingApi.cancelBooking(id)
      if (envelope.success && envelope.data) {
        const updated = envelope.data
        const idx = userBookings.value.findIndex((b) => b.id === id)
        if (idx !== -1) {
          userBookings.value.splice(idx, 1, updated)
        }
        if (currentBooking.value?.id === id) {
          currentBooking.value = updated
        }
      }
    } catch (err) {
      error.value = extractMessage(err, 'Failed to cancel booking')
      throw err
    }
  }

  /** Обнуляет всё состояние store (логаут, смена пользователя). */
  function reset(): void {
    userBookings.value = []
    currentBooking.value = null
    availability.value = null
    total.value = 0
    error.value = null
    isLoading.value = false
  }

  return {
    userBookings,
    currentBooking,
    availability,
    total,
    isLoading,
    error,
    fetchUserBookings,
    fetchBooking,
    checkAvailability,
    createBooking,
    cancelBooking,
    reset,
  }
})
