import { expect, test } from '../../fixtures/booking.fixture'
import {
  bookingEnvelope,
  errorEnvelope,
  mockAvailability,
  mockService,
  timeSlotAvailabilityEnvelope,
  type BookingFixture,
  type ServiceFixture,
  type SlotFixture,
} from '../../fixtures/booking.mocks'

const SERVICE_ID = 'aaaaaaaa-aaaa-4aaa-aaaa-aaaaaaaaaaaa'
const SLOT_1 = 'bbbbbbbb-bbbb-4bbb-bbbb-bbbbbbbbbbbb'
const SLOT_2 = 'cccccccc-cccc-4ccc-cccc-cccccccccccc'
const BOOKING_ID = 'dddddddd-dddd-4ddd-dddd-dddddddddddd'
const USER_ID = 'eeeeeeee-eeee-4eee-eeee-eeeeeeeeeeee'

const SERVICE: ServiceFixture = {
  id: SERVICE_ID,
  name: 'Мужская стрижка',
  description: 'Премиальный барбершоп',
  type: 'time_slot',
  priceAmount: 150000,
  priceCurrency: 'RUB',
  durationMinutes: 60,
  totalQuantity: null,
  categoryId: 'ffffffff-ffff-4fff-ffff-ffffffffffff',
  categoryName: 'Стрижки',
}

const SLOTS: SlotFixture[] = [
  {
    id: SLOT_1,
    startAt: '2026-05-01T10:00:00+00:00',
    endAt: '2026-05-01T11:00:00+00:00',
  },
  {
    id: SLOT_2,
    startAt: '2026-05-01T11:00:00+00:00',
    endAt: '2026-05-01T12:00:00+00:00',
  },
]

const CREATED_BOOKING: BookingFixture = {
  id: BOOKING_ID,
  userId: USER_ID,
  serviceId: SERVICE_ID,
  type: 'time_slot',
  status: 'pending',
  slotId: SLOT_1,
  startAt: '2026-05-01T10:00:00+00:00',
  endAt: '2026-05-01T11:00:00+00:00',
  totalAmount: 150000,
  totalCurrency: 'RUB',
  notes: 'E2E happy path',
}

test.describe('Booking flow — TIME_SLOT', () => {
  test.beforeEach(async ({ page }) => {
    await mockService(page, SERVICE)
    await mockAvailability(page, SERVICE_ID, timeSlotAvailabilityEnvelope(SLOTS))
  })

  test('user selects slot and is redirected to confirm page', async ({
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

    await bookingPage.selectDate('2026-05-01')
    await expect(bookingPage.slotButtons).toHaveCount(SLOTS.length)

    await bookingPage.slotBySlotId(SLOT_1).click()
    await expect(bookingPage.submitBtn).toBeEnabled()

    await bookingPage.fillNotes('E2E happy path')
    await bookingPage.submit()

    await bookingPage.expectRedirectToConfirm()
    await bookingConfirmPage.expectLoaded()
    expect(bookingConfirmPage.getBookingIdFromUrl()).toBe(BOOKING_ID)
  })

  test('shows error when slot is no longer available (409)', async ({
    page,
    bookingPage,
  }) => {
    await page.route('**/api/v1/bookings', async (route) => {
      if (route.request().method() !== 'POST') {
        await route.fallback()
        return
      }
      await route.fulfill({
        status: 409,
        json: errorEnvelope('BOOKING_SLOT_UNAVAILABLE', 'Slot is not available'),
      })
    })

    await bookingPage.goto(SERVICE_ID)
    await bookingPage.selectDate('2026-05-01')
    await bookingPage.slotBySlotId(SLOT_1).click()
    await bookingPage.submit()

    await bookingPage.expectError(/Slot is not available/i)
    await expect(bookingPage.page).toHaveURL(/\/book\//)
  })

  test('renders empty state when no slots returned', async ({ page, bookingPage }) => {
    await page.unroute(`**/api/v1/services/${SERVICE_ID}/availability**`)
    await mockAvailability(page, SERVICE_ID, timeSlotAvailabilityEnvelope([]))

    await bookingPage.goto(SERVICE_ID)
    await bookingPage.selectDate('2026-05-01')

    await expect(bookingPage.noSlots).toBeVisible()
    await bookingPage.expectSubmitDisabled()
  })
})
