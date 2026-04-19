import { apiClient } from './client'
import type { Envelope } from '@/types/auth.types'
import type { Booking, BookingStatus } from '@/types/booking.types'

/**
 * Params для listInbox.
 */
export interface InboxParams {
  status?: BookingStatus
  page?: number
  perPage?: number
}

/**
 * GET /organizations/{slug}/bookings — inbox запросов organization.
 *
 * TODO: backend endpoint пока не реализован. Stub возвращает raw envelope;
 * caller должен обработать 404/failed envelope.
 */
export async function listInbox(
  slug: string,
  params: InboxParams = {},
): Promise<Envelope<Booking[]>> {
  const resp = await apiClient.get<Envelope<Booking[]>>(
    `/organizations/${encodeURIComponent(slug)}/bookings`,
    { params },
  )
  return resp.data
}

/**
 * PATCH /bookings/{id}/confirm — confirm бронирование.
 *
 * TODO: backend endpoint пока не реализован.
 */
export async function confirm(bookingId: string): Promise<Envelope<Booking>> {
  const resp = await apiClient.patch<Envelope<Booking>>(
    `/bookings/${encodeURIComponent(bookingId)}/confirm`,
  )
  return resp.data
}

/**
 * PATCH /bookings/{id}/decline — decline бронирование.
 *
 * TODO: backend endpoint пока не реализован.
 */
export async function decline(bookingId: string): Promise<Envelope<Booking>> {
  const resp = await apiClient.patch<Envelope<Booking>>(
    `/bookings/${encodeURIComponent(bookingId)}/decline`,
  )
  return resp.data
}
