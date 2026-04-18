import type { Page, Route } from '@playwright/test'

/**
 * Билдеры для API-моков E2E тестов бронирования.
 *
 * Все помощники возвращают raw JSON в snake_case — точно так как шлёт
 * Laravel backend. Маппинг в camelCase делают `api/booking.api.ts` и
 * `api/availability.api.ts`.
 */

export interface ServiceFixture {
  id: string
  name: string
  description: string
  type: 'time_slot' | 'quantity'
  priceAmount: number
  priceCurrency: 'RUB' | 'USD' | 'EUR'
  durationMinutes: number | null
  totalQuantity: number | null
  categoryId: string
  categoryName: string
}

export interface SlotFixture {
  id: string
  startAt: string
  endAt: string
}

export interface BookingFixture {
  id: string
  userId: string
  serviceId: string
  type: 'time_slot' | 'quantity'
  status: 'pending' | 'confirmed' | 'cancelled' | 'completed'
  slotId?: string | null
  startAt?: string | null
  endAt?: string | null
  checkIn?: string | null
  checkOut?: string | null
  quantity?: number | null
  totalAmount: number
  totalCurrency: 'RUB' | 'USD' | 'EUR'
  notes?: string | null
}

/**
 * Формирует raw-JSON услуги для GET `/services/:id`.
 */
export function serviceEnvelope(svc: ServiceFixture): object {
  return {
    success: true,
    data: {
      id: svc.id,
      name: svc.name,
      description: svc.description,
      type: svc.type,
      price_amount: svc.priceAmount,
      price_currency: svc.priceCurrency,
      duration_minutes: svc.durationMinutes,
      total_quantity: svc.totalQuantity,
      category_id: svc.categoryId,
      category_name: svc.categoryName,
      subcategory_id: null,
      subcategory_name: null,
      is_active: true,
      images: [],
      created_at: '2026-04-01T10:00:00+00:00',
      updated_at: '2026-04-01T10:00:00+00:00',
    },
    error: null,
    meta: null,
  }
}

/**
 * Формирует raw-JSON доступности time_slot для GET `/services/:id/availability`.
 */
export function timeSlotAvailabilityEnvelope(slots: SlotFixture[]): object {
  return {
    success: true,
    data: {
      type: 'time_slot',
      available: slots.length > 0,
      slots: slots.map((slot) => ({
        id: slot.id,
        start_at: slot.startAt,
        end_at: slot.endAt,
      })),
    },
    error: null,
    meta: null,
  }
}

/**
 * Формирует raw-JSON доступности quantity для GET `/services/:id/availability`.
 */
export function quantityAvailabilityEnvelope(params: {
  total: number
  booked: number
  requested: number
}): object {
  const availableQuantity = params.total - params.booked
  return {
    success: true,
    data: {
      type: 'quantity',
      available: availableQuantity >= params.requested,
      total: params.total,
      booked: params.booked,
      available_quantity: availableQuantity,
      requested: params.requested,
    },
    error: null,
    meta: null,
  }
}

/**
 * Формирует raw-JSON бронирования (ответ `/bookings` detail/create/cancel).
 */
export function bookingEnvelope(b: BookingFixture): object {
  return {
    success: true,
    data: {
      id: b.id,
      user_id: b.userId,
      service_id: b.serviceId,
      type: b.type,
      status: b.status,
      slot_id: b.slotId ?? null,
      start_at: b.startAt ?? null,
      end_at: b.endAt ?? null,
      check_in: b.checkIn ?? null,
      check_out: b.checkOut ?? null,
      quantity: b.quantity ?? null,
      total_price: { amount: b.totalAmount, currency: b.totalCurrency },
      notes: b.notes ?? null,
      created_at: '2026-04-01T10:00:00+00:00',
      updated_at: '2026-04-01T10:00:00+00:00',
    },
    error: null,
    meta: null,
  }
}

/**
 * Формирует raw-JSON envelope ошибки (для 4xx/5xx ответов).
 */
export function errorEnvelope(code: string, message: string): object {
  return {
    success: false,
    data: null,
    error: { code, message, details: null },
    meta: null,
  }
}

/**
 * Формирует raw-JSON списка бронирований (ответ `/bookings` list).
 */
export function bookingListEnvelope(bookings: BookingFixture[]): object {
  return {
    success: true,
    data: bookings.map((b) => {
      const env = bookingEnvelope(b) as { data: unknown }
      return env.data
    }),
    error: null,
    meta: {
      total: bookings.length,
      page: 1,
      per_page: 20,
      last_page: 1,
    },
  }
}

/**
 * Регистрирует мок GET `/api/v1/services/:id` возвращающий заданную услугу.
 */
export async function mockService(page: Page, svc: ServiceFixture): Promise<void> {
  await page.route(`**/api/v1/services/${svc.id}`, async (route: Route) => {
    if (route.request().method() !== 'GET') {
      await route.fallback()
      return
    }
    await route.fulfill({ status: 200, json: serviceEnvelope(svc) })
  })
}

/**
 * Регистрирует мок GET `/api/v1/services/:id/availability` с заданным ответом.
 */
export async function mockAvailability(
  page: Page,
  serviceId: string,
  envelope: object,
): Promise<void> {
  await page.route(
    `**/api/v1/services/${serviceId}/availability**`,
    async (route: Route) => {
      if (route.request().method() !== 'GET') {
        await route.fallback()
        return
      }
      await route.fulfill({ status: 200, json: envelope })
    },
  )
}
