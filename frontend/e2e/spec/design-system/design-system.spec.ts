import { expect, test } from '@playwright/test'

test.describe('DesignSystem', () => {
  test('page loads and shows sections', async ({ page }) => {
    await page.goto('/design-system')
    await expect(page.getByRole('heading', { name: /design system/i }).first()).toBeVisible()
    await expect(page.locator('#colors')).toBeVisible()
    await expect(page.locator('#buttons')).toBeVisible()
  })

  test('theme toggle switches class', async ({ page }) => {
    await page.goto('/design-system')
    const html = page.locator('html')
    const initial = (await html.getAttribute('class')) ?? ''
    await page.getByTestId('app-header-theme-toggle').click()
    await expect
      .poll(async () => (await html.getAttribute('class')) ?? '')
      .not.toBe(initial)
  })
})
