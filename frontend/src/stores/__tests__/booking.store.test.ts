import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { useBookingStore } from '@/stores/booking.store'
import type { ApiEnvelope } from '@/types/catalog.types'
import type {
  Availability,
  Booking,
  QuantityAvailability,
  TimeSlotAvailability,
} from '@/types/booking.types'

vi.mock('@/api/booking.api', () => ({
  listBookings: vi.fn(),
  getBooking: vi.fn(),
  createBooking: vi.fn(),
  cancelBooking: vi.fn(),
}))

vi.mock('@/api/availability.api', () => ({
  checkAvailability: vi.fn(),
}))

import * as bookingApi from '@/api/booking.api'
import * as availabilityApi from '@/api/availability.api'

const mockedListBookings = vi.mocked(bookingApi.listBookings)
const mockedCreateBooking = vi.mocked(bookingApi.createBooking)
const mockedCancelBooking = vi.mocked(bookingApi.cancelBooking)
const mockedCheckAvailability = vi.mocked(availabilityApi.checkAvailability)

const sampleBooking: Booking = {
  id: '11111111-1111-1111-1111-111111111111',
  userId: 'user-1',
  serviceId: 'service-1',
  type: 'time_slot',
  status: 'pending',
  slotId: 'slot-1',
  startAt: '2026-04-20T10:00:00+00:00',
  endAt: '2026-04-20T11:00:00+00:00',
  checkIn: null,
  checkOut: null,
  quantity: null,
  totalPriceAmount: 150000,
  totalPriceCurrency: 'RUB',
  notes: null,
  createdAt: '2026-04-18T09:00:00+00:00',
  updatedAt: '2026-04-18T09:00:00+00:00',
}

const cancelledBooking: Booking = {
  ...sampleBooking,
  status: 'cancelled',
}

const timeSlotAvailability: TimeSlotAvailability = {
  type: 'time_slot',
  available: true,
  slots: [
    { id: 'slot-1', startAt: '2026-04-20T10:00:00+00:00', endAt: '2026-04-20T11:00:00+00:00' },
  ],
}

const quantityAvailability: QuantityAvailability = {
  type: 'quantity',
  available: true,
  total: 10,
  booked: 3,
  availableQuantity: 7,
  requested: 2,
}

function envelopeOk<T>(data: T, meta: ApiEnvelope<T>['meta'] = null): ApiEnvelope<T> {
  return { success: true, data, error: null, meta }
}

function envelopeError<T>(code: string, message: string): ApiEnvelope<T> {
  return {
    success: false,
    data: null,
    error: { code, message, details: null },
    meta: null,
  }
}

describe('useBookingStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('createBooking успех — обновляет currentBooking и возвращает Booking', async () => {
    mockedCreateBooking.mockResolvedValueOnce(envelopeOk(sampleBooking))

    const store = useBookingStore()
    const result = await store.createBooking({
      serviceId: 'service-1',
      type: 'time_slot',
      slotId: 'slot-1',
    })

    expect(result).toEqual(sampleBooking)
    expect(store.currentBooking).toEqual(sampleBooking)
    expect(store.error).toBeNull()
    expect(store.isLoading).toBe(false)
  })

  it('createBooking ошибка envelope — сохраняет error и пробрасывает исключение', async () => {
    mockedCreateBooking.mockResolvedValueOnce(
      envelopeError<Booking>('SLOT_UNAVAILABLE', 'Slot already booked'),
    )

    const store = useBookingStore()

    await expect(
      store.createBooking({ serviceId: 'service-1', type: 'time_slot', slotId: 'slot-1' }),
    ).rejects.toThrow('Slot already booked')

    expect(store.error).toBe('Slot already booked')
    expect(store.currentBooking).toBeNull()
    expect(store.isLoading).toBe(false)
  })

  it('fetchUserBookings — записывает userBookings и total из meta', async () => {
    mockedListBookings.mockResolvedValueOnce(
      envelopeOk([sampleBooking], { total: 42, page: 1, perPage: 20, lastPage: 3 }),
    )

    const store = useBookingStore()
    await store.fetchUserBookings({ status: 'pending', page: 1, perPage: 20 })

    expect(store.userBookings).toEqual([sampleBooking])
    expect(store.total).toBe(42)
    expect(store.isLoading).toBe(false)
    expect(store.error).toBeNull()
    expect(mockedListBookings).toHaveBeenCalledWith({
      status: 'pending',
      page: 1,
      perPage: 20,
    })
  })

  it('fetchUserBookings — фоллбэк total на длину массива если meta нет', async () => {
    mockedListBookings.mockResolvedValueOnce(envelopeOk([sampleBooking]))

    const store = useBookingStore()
    await store.fetchUserBookings()

    expect(store.total).toBe(1)
  })

  it('cancelBooking — обновляет запись в userBookings по id', async () => {
    const store = useBookingStore()
    store.userBookings = [sampleBooking]

    mockedCancelBooking.mockResolvedValueOnce(envelopeOk(cancelledBooking))

    await store.cancelBooking(sampleBooking.id)

    expect(store.userBookings).toHaveLength(1)
    expect(store.userBookings[0]?.status).toBe('cancelled')
    expect(mockedCancelBooking).toHaveBeenCalledWith(sampleBooking.id)
  })

  it('checkAvailability — time_slot: записывает слоты', async () => {
    mockedCheckAvailability.mockResolvedValueOnce(
      envelopeOk<Availability>(timeSlotAvailability),
    )

    const store = useBookingStore()
    await store.checkAvailability('service-1', { type: 'time_slot', date: '2026-04-20' })

    expect(store.availability).toEqual(timeSlotAvailability)
    expect(store.isLoading).toBe(false)
  })

  it('checkAvailability — quantity: записывает total/booked/availableQuantity', async () => {
    mockedCheckAvailability.mockResolvedValueOnce(
      envelopeOk<Availability>(quantityAvailability),
    )

    const store = useBookingStore()
    await store.checkAvailability('service-1', {
      type: 'quantity',
      checkIn: '2026-04-20',
      checkOut: '2026-04-22',
      requested: 2,
    })

    expect(store.availability).toEqual(quantityAvailability)
    expect(mockedCheckAvailability).toHaveBeenCalledWith('service-1', {
      type: 'quantity',
      checkIn: '2026-04-20',
      checkOut: '2026-04-22',
      requested: 2,
    })
  })

  it('reset — обнуляет всё состояние', async () => {
    const store = useBookingStore()
    store.userBookings = [sampleBooking]
    store.currentBooking = sampleBooking
    store.availability = timeSlotAvailability
    store.total = 5
    store.error = 'something'

    store.reset()

    expect(store.userBookings).toEqual([])
    expect(store.currentBooking).toBeNull()
    expect(store.availability).toBeNull()
    expect(store.total).toBe(0)
    expect(store.error).toBeNull()
    expect(store.isLoading).toBe(false)
  })
})
