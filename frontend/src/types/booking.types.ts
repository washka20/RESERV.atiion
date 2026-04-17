/**
 * Доменные типы модуля бронирования.
 *
 * Соответствуют JSON-схеме публичного API `/api/v1/bookings` и `/api/v1/services/{id}/availability`.
 * Backend возвращает поля в snake_case — конвертация в camelCase делается в api/booking.api.ts
 * и api/availability.api.ts.
 */

/** Тип бронирования: слот времени или количество единиц на диапазон дат. */
export type BookingType = 'time_slot' | 'quantity'

/** Статус бронирования. */
export type BookingStatus = 'pending' | 'confirmed' | 'cancelled' | 'completed'

/** Слот времени, принадлежит услуге типа time_slot. */
export interface TimeSlotItem {
  id: string
  serviceId: string
  startAt: string
  endAt: string
  isBooked: boolean
  bookingId: string | null
}

/** Бронирование (detail view). */
export interface Booking {
  id: string
  userId: string
  serviceId: string
  type: BookingType
  status: BookingStatus
  /** Заполнено только для type=time_slot. */
  slotId: string | null
  /** Начало слота — только для type=time_slot. */
  startAt: string | null
  /** Конец слота — только для type=time_slot. */
  endAt: string | null
  /** Дата заезда — только для type=quantity. */
  checkIn: string | null
  /** Дата выезда — только для type=quantity. */
  checkOut: string | null
  /** Количество — только для type=quantity. */
  quantity: number | null
  /** Стоимость в минимальных единицах валюты (копейках/центах). */
  totalPriceAmount: number
  totalPriceCurrency: string
  notes: string | null
  createdAt: string
  updatedAt: string
}

/** Доступность по слотам времени (type=time_slot). */
export interface TimeSlotAvailability {
  type: 'time_slot'
  available: boolean
  slots: { id: string; startAt: string; endAt: string }[]
}

/** Доступность по количеству единиц на диапазон дат (type=quantity). */
export interface QuantityAvailability {
  type: 'quantity'
  available: boolean
  total: number
  booked: number
  availableQuantity: number
  requested: number
}

/** Объединённый тип ответа endpoint-а доступности. */
export type Availability = TimeSlotAvailability | QuantityAvailability

/** Payload для POST /bookings. */
export interface CreateBookingPayload {
  serviceId: string
  type: BookingType
  /** UUID слота — обязательно если type=time_slot. */
  slotId?: string
  /** Дата заезда Y-m-d — обязательно если type=quantity. */
  checkIn?: string
  /** Дата выезда Y-m-d — обязательно если type=quantity. */
  checkOut?: string
  /** Количество — обязательно если type=quantity. */
  quantity?: number
  notes?: string
}

/** Query-параметры для GET /services/{id}/availability. */
export interface AvailabilityParams {
  type: BookingType
  /** Для type=time_slot: день Y-m-d. */
  date?: string
  /** Для type=quantity: дата заезда Y-m-d. */
  checkIn?: string
  /** Для type=quantity: дата выезда Y-m-d. */
  checkOut?: string
  /** Для type=quantity: запрошенное количество. */
  requested?: number
}
