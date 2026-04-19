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
  _auth: void
}

export const test = base.extend<BookingFixtures>({
  /**
   * Auto-fixture: кладёт fake JWT в localStorage до первой навигации.
   * Router guard (meta.requiresAuth) редиректит на /catalog когда токена нет —
   * в e2e backend замоканы через page.route, реальный JWT не нужен.
   */
  _auth: [
    async ({ page }, use) => {
      // Fake JWT + default моки auth.store.hydrate endpoints (/auth/me, /me/memberships).
      // Без моков hydrate() валит loadMe → user=null → guard редиректит на /login.
      await page.addInitScript(() => {
        window.localStorage.setItem('auth:token', 'e2e-fake-token')
      })
      await page.route('**/api/v1/auth/me', async (route) => {
        await route.fulfill({
          status: 200,
          json: {
            success: true,
            data: {
              id: 'e2e-user',
              email: 'e2e@test.ru',
              first_name: 'E2E',
              last_name: 'User',
              middle_name: null,
              roles: [],
              email_verified_at: '2026-01-01T00:00:00Z',
              created_at: '2026-01-01T00:00:00Z',
            },
            error: null,
          },
        })
      })
      await page.route('**/api/v1/me/memberships', async (route) => {
        await route.fulfill({
          status: 200,
          json: { success: true, data: [], error: null },
        })
      })
      await use()
    },
    { auto: true },
  ],
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
