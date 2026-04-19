import { expect, test } from '@playwright/test'

/**
 * Auth login flow — smoke E2E.
 *
 * Моки на границе API: envelope соответствует backend response shape
 * (snake_case в data.* — `access_token`, `refresh_token`, `first_name`).
 * Clearing localStorage в beforeEach — изоляция от других тестов,
 * которые могут оставить fake JWT (см. booking.fixture).
 */
test.describe('Auth — login flow', () => {
  test.beforeEach(async ({ page }) => {
    await page.addInitScript(() => {
      try {
        window.localStorage.clear()
      } catch {
        /* noop */
      }
    })
  })

  test('login happy path → catalog', async ({ page }) => {
    await page.route('**/api/v1/auth/login', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          success: true,
          data: {
            access_token: 'fake-at',
            refresh_token: 'fake-rt',
            expires_in: 3600,
            token_type: 'Bearer',
          },
          error: null,
          meta: null,
        }),
      })
    })

    await page.route('**/api/v1/auth/me', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          success: true,
          data: {
            id: 'u1',
            email: 'test@test.ru',
            first_name: 'Тест',
            last_name: 'Тестов',
            middle_name: null,
            roles: [],
            email_verified_at: '2026-01-01T00:00:00Z',
            created_at: '2026-01-01T00:00:00Z',
          },
          error: null,
          meta: null,
        }),
      })
    })

    await page.route('**/api/v1/me/memberships', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          success: true,
          data: [],
          error: null,
          meta: null,
        }),
      })
    })

    await page.goto('/login')
    await page.getByTestId('auth-login-email-input').fill('test@test.ru')
    await page.getByTestId('auth-login-password-input').fill('password123')
    await page.getByTestId('auth-login-submit-btn').click()

    await expect(page).toHaveURL(/\/catalog/)
  })

  test('login fail 401 → error visible', async ({ page }) => {
    await page.route('**/api/v1/auth/login', async (route) => {
      await route.fulfill({
        status: 401,
        contentType: 'application/json',
        body: JSON.stringify({
          success: false,
          data: null,
          error: { code: 'INVALID', message: 'Invalid credentials' },
          meta: null,
        }),
      })
    })

    await page.goto('/login')
    await page.getByTestId('auth-login-email-input').fill('test@test.ru')
    await page.getByTestId('auth-login-password-input').fill('wrong')
    await page.getByTestId('auth-login-submit-btn').click()

    await expect(page.getByText(/invalid credentials|неверн/i)).toBeVisible()
  })
})
