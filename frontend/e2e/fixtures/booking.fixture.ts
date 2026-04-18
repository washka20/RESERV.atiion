import { test as base } from '@playwright/test'
import { BookingPage } from '../pages/BookingPage'
import { BookingConfirmPage } from '../pages/BookingConfirmPage'
import { DashboardPage } from '../pages/DashboardPage'

/**
 * Фикстуры модуля бронирования.
 *
 * Предоставляют bare POM (без навигации) для тестов, которые сами
 * решают когда переходить на страницу — часть тестов сначала мокирует
 * запросы через `page.route`, потом открывает страницу.
 *
 * Интеграция с реальным backend не используется: `/bookings` и
 * `/services/{id}/availability` требуют JWT, а frontend пока не прокидывает
 * Authorization-хедер. E2E тесты мокируют все REST-ответы через
 * `page.route` в самих spec-файлах.
 */
type BookingFixtures = {
  bookingPage: BookingPage
  bookingConfirmPage: BookingConfirmPage
  dashboardPage: DashboardPage
}

export const test = base.extend<BookingFixtures>({
  bookingPage: async ({ page }, use) => {
    await use(new BookingPage(page))
  },
  bookingConfirmPage: async ({ page }, use) => {
    await use(new BookingConfirmPage(page))
  },
  dashboardPage: async ({ page }, use) => {
    await use(new DashboardPage(page))
  },
})

export { expect } from '@playwright/test'
