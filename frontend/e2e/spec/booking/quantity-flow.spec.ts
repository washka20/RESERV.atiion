import { expect, test } from '../../fixtures/booking.fixture'
import {
  bookingEnvelope,
  errorEnvelope,
  mockAvailability,
  mockService,
  quantityAvailabilityEnvelope,
  type BookingFixture,
  type ServiceFixture,
} from '../../fixtures/booking.mocks'

const SERVICE_ID = '11111111-1111-4111-1111-111111111111'
const BOOKING_ID = '22222222-2222-4222-2222-222222222222'
const USER_ID = '33333333-3333-4333-3333-333333333333'

const SERVICE: ServiceFixture = {
  id: SERVICE_ID,
  name: 'Апартаменты у моря',
  description: 'Квартира на сутки',
  type: 'quantity',
  priceAmount: 500000,
  priceCurrency: 'RUB',
  durationMinutes: null,
  totalQuantity: 5,
  categoryId: '44444444-4444-4444-4444-444444444444',
  categoryName: 'Жильё',
}

const CREATED_BOOKING: BookingFixture = {
  id: BOOKING_ID,
  userId: USER_ID,
  serviceId: SERVICE_ID,
  type: 'quantity',
  status: 'pending',
  checkIn: '2026-05-10',
  checkOut: '2026-05-12',
  quantity: 2,
  totalAmount: 2000000,
  totalCurrency: 'RUB',
  notes: null,
}

test.describe('Booking flow — QUANTITY', () => {
  test.beforeEach(async ({ page }) => {
    await mockService(page, SERVICE)
    await mockAvailability(
      page,
      SERVICE_ID,
      quantityAvailabilityEnvelope({ total: 5, booked: 1, requested: 2 }),
    )
  })

  test('user picks date range and quantity and lands on confirm page', async ({
    page,
    bookingPage,
    bookingConfirmPage,
  }) => {
    await page.route('**/api/v1/bookings', async (route) => {
      if (route.request().method() !== 'POST') {
        await route.fallback()
        return
      }
      await route.fulfill({ status: 201, json: bookingEnvelope(CREATED_BOOKING) })
    })
    await page.route(`**/api/v1/bookings/${BOOKING_ID}`, async (route) => {
      if (route.request().method() !== 'GET') {
        await route.fallback()
        return
      }
      await route.fulfill({ status: 200, json: bookingEnvelope(CREATED_BOOKING) })
    })

    await bookingPage.goto(SERVICE_ID)
    await bookingPage.expectSubmitDisabled()

    await bookingPage.setDateRange('2026-05-10', '2026-05-12')
    await bookingPage.setQuantity(2)

    await expect(bookingPage.availabilityInfo).toBeVisible()
    await expect(bookingPage.availabilityInfo).toContainText(/4\s+доступно\s+из\s+5/)
    await expect(bookingPage.submitBtn).toBeEnabled()

    await bookingPage.submit()

    await bookingPage.expectRedirectToConfirm()
    await bookingConfirmPage.expectLoaded()
    expect(bookingConfirmPage.getBookingIdFromUrl()).toBe(BOOKING_ID)
  })

  test('shows error when quantity exceeds availability (422)', async ({
    page,
    bookingPage,
  }) => {
    await page.unroute(`**/api/v1/services/${SERVICE_ID}/availability**`)
    await mockAvailability(
      page,
      SERVICE_ID,
      quantityAvailabilityEnvelope({ total: 5, booked: 4, requested: 3 }),
    )

    await page.route('**/api/v1/bookings', async (route) => {
      if (route.request().method() !== 'POST') {
        await route.fallback()
        return
      }
      await route.fulfill({
        status: 422,
        json: errorEnvelope('BOOKING_QUANTITY_EXCEEDED', 'Requested quantity exceeds availability'),
      })
    })

    await bookingPage.goto(SERVICE_ID)
    await bookingPage.setDateRange('2026-05-10', '2026-05-12')
    await bookingPage.setQuantity(3)

    await expect(bookingPage.availabilityInfo).toContainText(/1\s+доступно\s+из\s+5/)
    await bookingPage.expectSubmitDisabled()
  })

  test('submit disabled when range is invalid (checkout before checkin)', async ({
    bookingPage,
  }) => {
    await bookingPage.goto(SERVICE_ID)
    await bookingPage.setDateRange('2026-05-15', '2026-05-10')
    await bookingPage.setQuantity(1)

    await expect(bookingPage.submitBtn).toBeDisabled()
  })
})
