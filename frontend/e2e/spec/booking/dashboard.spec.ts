import { expect, test } from '../../fixtures/booking.fixture'
import {
  bookingEnvelope,
  bookingListEnvelope,
  type BookingFixture,
} from '../../fixtures/booking.mocks'

const USER_ID = '55555555-5555-4555-5555-555555555555'

const PENDING_BOOKING: BookingFixture = {
  id: '66666666-6666-4666-6666-666666666666',
  userId: USER_ID,
  serviceId: '77777777-7777-4777-7777-777777777777',
  type: 'time_slot',
  status: 'pending',
  slotId: '88888888-8888-4888-8888-888888888888',
  startAt: '2026-05-01T10:00:00+00:00',
  endAt: '2026-05-01T11:00:00+00:00',
  totalAmount: 150000,
  totalCurrency: 'RUB',
}

const COMPLETED_BOOKING: BookingFixture = {
  id: '99999999-9999-4999-9999-999999999999',
  userId: USER_ID,
  serviceId: '77777777-7777-4777-7777-777777777777',
  type: 'quantity',
  status: 'completed',
  checkIn: '2026-04-01',
  checkOut: '2026-04-03',
  quantity: 1,
  totalAmount: 500000,
  totalCurrency: 'RUB',
}

test.describe('Dashboard — список бронирований и отмена', () => {
  test('renders two bookings with correct statuses', async ({ page, dashboardPage }) => {
    await page.route('**/api/v1/bookings*', async (route) => {
      if (route.request().method() !== 'GET') {
        await route.fallback()
        return
      }
      await route.fulfill({
        status: 200,
        json: bookingListEnvelope([PENDING_BOOKING, COMPLETED_BOOKING]),
      })
    })

    await dashboardPage.goto()

    await expect(dashboardPage.cards).toHaveCount(2)
    await expect(dashboardPage.statusBadge(PENDING_BOOKING.id)).toContainText(/В обработке/)
    await expect(dashboardPage.statusBadge(COMPLETED_BOOKING.id)).toContainText(/Выполнено/)
  })

  test('cancel button visible only for pending booking', async ({ page, dashboardPage }) => {
    await page.route('**/api/v1/bookings*', async (route) => {
      if (route.request().method() !== 'GET') {
        await route.fallback()
        return
      }
      await route.fulfill({
        status: 200,
        json: bookingListEnvelope([PENDING_BOOKING, COMPLETED_BOOKING]),
      })
    })

    await dashboardPage.goto()

    await expect(dashboardPage.cancelButton(PENDING_BOOKING.id)).toBeVisible()
    await expect(dashboardPage.cancelButton(COMPLETED_BOOKING.id)).toHaveCount(0)
  })

  test('cancelling a booking updates status badge', async ({ page, dashboardPage }) => {
    await page.route('**/api/v1/bookings*', async (route) => {
      if (route.request().method() !== 'GET') {
        await route.fallback()
        return
      }
      await route.fulfill({
        status: 200,
        json: bookingListEnvelope([PENDING_BOOKING]),
      })
    })

    await page.route(`**/api/v1/bookings/${PENDING_BOOKING.id}/cancel`, async (route) => {
      if (route.request().method() !== 'PATCH') {
        await route.fallback()
        return
      }
      await route.fulfill({
        status: 200,
        json: bookingEnvelope({ ...PENDING_BOOKING, status: 'cancelled' }),
      })
    })

    await dashboardPage.goto()
    await expect(dashboardPage.statusBadge(PENDING_BOOKING.id)).toContainText(/В обработке/)

    await dashboardPage.cancel(PENDING_BOOKING.id)

    await expect(dashboardPage.statusBadge(PENDING_BOOKING.id)).toContainText(/Отменено/)
    await expect(dashboardPage.cancelButton(PENDING_BOOKING.id)).toHaveCount(0)
  })

  test('status filter refreshes list with query param', async ({ page, dashboardPage }) => {
    const requestedStatuses: string[] = []

    await page.route('**/api/v1/bookings*', async (route) => {
      if (route.request().method() !== 'GET') {
        await route.fallback()
        return
      }
      const url = new URL(route.request().url())
      const status = url.searchParams.get('status')
      requestedStatuses.push(status ?? '__none__')

      const list = status === 'completed' ? [COMPLETED_BOOKING] : [PENDING_BOOKING, COMPLETED_BOOKING]
      await route.fulfill({ status: 200, json: bookingListEnvelope(list) })
    })

    await dashboardPage.goto()
    await expect(dashboardPage.cards).toHaveCount(2)

    await dashboardPage.filterByStatus('completed')
    await expect(dashboardPage.cards).toHaveCount(1)
    await expect(dashboardPage.statusBadge(COMPLETED_BOOKING.id)).toContainText(/Выполнено/)
    expect(requestedStatuses).toContain('completed')
  })
})
