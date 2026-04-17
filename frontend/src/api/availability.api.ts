import { apiClient } from './client'
import type { ApiEnvelope, ApiError, PaginationMeta } from '@/types/catalog.types'
import type {
  Availability,
  AvailabilityParams,
  QuantityAvailability,
  TimeSlotAvailability,
} from '@/types/booking.types'

interface RawTimeSlotAvailability {
  type: 'time_slot'
  available: boolean
  slots: { id: string; start_at: string; end_at: string }[]
}

interface RawQuantityAvailability {
  type: 'quantity'
  available: boolean
  total: number
  booked: number
  available_quantity: number
  requested: number
}

type RawAvailability = RawTimeSlotAvailability | RawQuantityAvailability

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
 * Преобразует raw-ответ endpoint-а доступности в доменный `Availability`.
 * Полиморфно по `raw.type`: для `time_slot` маппит slots.start_at/end_at,
 * для `quantity` — total/booked/available_quantity/requested.
 */
export function mapAvailability(raw: RawAvailability): Availability {
  if (raw.type === 'time_slot') {
    const mapped: TimeSlotAvailability = {
      type: 'time_slot',
      available: raw.available,
      slots: raw.slots.map((slot) => ({
        id: slot.id,
        startAt: slot.start_at,
        endAt: slot.end_at,
      })),
    }
    return mapped
  }

  const mapped: QuantityAvailability = {
    type: 'quantity',
    available: raw.available,
    total: raw.total,
    booked: raw.booked,
    availableQuantity: raw.available_quantity,
    requested: raw.requested,
  }
  return mapped
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

function buildAvailabilityQueryParams(
  params: AvailabilityParams,
): Record<string, string | number> {
  const result: Record<string, string | number> = { type: params.type }
  if (params.date) result.date = params.date
  if (params.checkIn) result.check_in = params.checkIn
  if (params.checkOut) result.check_out = params.checkOut
  if (typeof params.requested === 'number') result.requested = params.requested
  return result
}

/**
 * Проверяет доступность услуги.
 *
 * Для TIME_SLOT (`type=time_slot`, `date=Y-m-d`) — возвращает список свободных слотов.
 * Для QUANTITY (`type=quantity`, `check_in`, `check_out`, `requested`) — total/booked/availableQuantity.
 *
 * @param serviceId UUID услуги
 * @param params параметры запроса
 * @returns envelope с типом доступности
 */
export async function checkAvailability(
  serviceId: string,
  params: AvailabilityParams,
): Promise<ApiEnvelope<Availability>> {
  const response = await apiClient.get<RawApiEnvelope<RawAvailability>>(
    `/services/${serviceId}/availability`,
    { params: buildAvailabilityQueryParams(params) },
  )
  return mapEnvelope(response.data, mapAvailability)
}
