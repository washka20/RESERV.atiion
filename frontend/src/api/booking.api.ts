import { apiClient } from './client'
import type { ApiEnvelope, ApiError, PaginationMeta } from '@/types/catalog.types'
import type {
  Booking,
  BookingStatus,
  BookingType,
  CreateBookingPayload,
} from '@/types/booking.types'

interface RawBooking {
  id: string
  user_id: string
  service_id: string
  type: BookingType
  status: BookingStatus
  slot_id: string | null
  start_at: string | null
  end_at: string | null
  check_in: string | null
  check_out: string | null
  quantity: number | null
  total_price: { amount: number; currency: string }
  notes: string | null
  created_at: string
  updated_at: string
}

interface RawPaginationMeta {
  total: number
  page: number
  per_page: number
  last_page: number
}

interface RawApiEnvelope<T> {
  success: boolean
  data: T | null
  error: ApiError | null
  meta: RawPaginationMeta | null
}

/**
 * Преобразует JSON-ответ бэка (snake_case) в доменный `Booking` (camelCase).
 */
export function mapBooking(raw: RawBooking): Booking {
  return {
    id: raw.id,
    userId: raw.user_id,
    serviceId: raw.service_id,
    type: raw.type,
    status: raw.status,
    slotId: raw.slot_id,
    startAt: raw.start_at,
    endAt: raw.end_at,
    checkIn: raw.check_in,
    checkOut: raw.check_out,
    quantity: raw.quantity,
    totalPriceAmount: raw.total_price.amount,
    totalPriceCurrency: raw.total_price.currency,
    notes: raw.notes,
    createdAt: raw.created_at,
    updatedAt: raw.updated_at,
  }
}

function mapPaginationMeta(raw: RawPaginationMeta | null): PaginationMeta | null {
  if (raw === null) {
    return null
  }
  return {
    total: raw.total,
    page: raw.page,
    perPage: raw.per_page,
    lastPage: raw.last_page,
  }
}

function mapEnvelope<TRaw, T>(
  raw: RawApiEnvelope<TRaw>,
  mapData: (data: TRaw) => T,
): ApiEnvelope<T> {
  return {
    success: raw.success,
    data: raw.data === null ? null : mapData(raw.data),
    error: raw.error,
    meta: mapPaginationMeta(raw.meta),
  }
}

/** Параметры запроса списка бронирований текущего пользователя. */
export interface ListBookingsParams {
  status?: BookingStatus | string
  page?: number
  perPage?: number
}

function buildListQueryParams(params: ListBookingsParams): Record<string, string | number> {
  const result: Record<string, string | number> = {}
  if (params.status) result.status = params.status
  if (typeof params.page === 'number') result.page = params.page
  if (typeof params.perPage === 'number') result.per_page = params.perPage
  return result
}

/**
 * Получить список бронирований текущего пользователя.
 *
 * @param params фильтр по статусу и параметры пагинации
 * @returns envelope со списком бронирований и meta пагинации
 */
export async function listBookings(
  params: ListBookingsParams = {},
): Promise<ApiEnvelope<Booking[]>> {
  const response = await apiClient.get<RawApiEnvelope<RawBooking[]>>('/bookings', {
    params: buildListQueryParams(params),
  })
  return mapEnvelope(response.data, (items) => items.map(mapBooking))
}

/**
 * Получить бронирование по идентификатору.
 *
 * @param id UUID бронирования
 * @returns envelope с полной карточкой бронирования
 */
export async function getBooking(id: string): Promise<ApiEnvelope<Booking>> {
  const response = await apiClient.get<RawApiEnvelope<RawBooking>>(`/bookings/${id}`)
  return mapEnvelope(response.data, mapBooking)
}

/**
 * Сериализует payload создания в snake_case JSON для бэка,
 * отбрасывая `undefined`-поля.
 */
function serializeCreatePayload(payload: CreateBookingPayload): Record<string, unknown> {
  const body: Record<string, unknown> = {
    service_id: payload.serviceId,
    type: payload.type,
  }
  if (payload.slotId !== undefined) body.slot_id = payload.slotId
  if (payload.checkIn !== undefined) body.check_in = payload.checkIn
  if (payload.checkOut !== undefined) body.check_out = payload.checkOut
  if (payload.quantity !== undefined) body.quantity = payload.quantity
  if (payload.notes !== undefined) body.notes = payload.notes
  return body
}

/**
 * Создать бронирование.
 *
 * @param payload данные бронирования
 * @returns envelope с созданным бронированием; при конфликте слота бэк возвращает 409
 *          с error envelope (axios бросит исключение — обработка в store).
 */
export async function createBooking(
  payload: CreateBookingPayload,
): Promise<ApiEnvelope<Booking>> {
  const response = await apiClient.post<RawApiEnvelope<RawBooking>>(
    '/bookings',
    serializeCreatePayload(payload),
  )
  return mapEnvelope(response.data, mapBooking)
}

/**
 * Отменить бронирование.
 *
 * @param id UUID бронирования
 * @returns envelope с обновлённым бронированием (status=cancelled)
 */
export async function cancelBooking(id: string): Promise<ApiEnvelope<Booking>> {
  const response = await apiClient.patch<RawApiEnvelope<RawBooking>>(
    `/bookings/${id}/cancel`,
  )
  return mapEnvelope(response.data, mapBooking)
}
