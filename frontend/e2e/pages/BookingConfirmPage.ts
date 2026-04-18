import { expect, type Locator, type Page } from '@playwright/test'

/**
 * Page Object для страницы подтверждения бронирования `/bookings/:id`.
 *
 * Отображает карточку созданного бронирования, кнопку перехода в кабинет
 * и детализацию по типу бронирования (slot vs range + quantity).
 */
export class BookingConfirmPage {
  readonly page: Page

  readonly container: Locator
  readonly card: Locator
  readonly status: Locator
  readonly idEl: Locator
  readonly service: Locator
  readonly total: Locator
  readonly dashboardBtn: Locator

  constructor(page: Page) {
    this.page = page

    this.container = page.getByTestId('booking-confirm-page')
    this.card = page.getByTestId('booking-confirm-card')
    this.status = page.getByTestId('booking-confirm-status')
    this.idEl = page.getByTestId('booking-confirm-id')
    this.service = page.getByTestId('booking-confirm-service')
    this.total = page.getByTestId('booking-confirm-total')
    this.dashboardBtn = page.getByTestId('booking-confirm-dashboard-btn')
  }

  /**
   * Проверяет что карточка подтверждения загружена.
   */
  async expectLoaded(): Promise<void> {
    await expect(this.container).toBeVisible()
    await expect(this.card).toBeVisible()
  }

  /**
   * Извлекает UUID бронирования из текущего URL.
   * Бросает исключение если URL не соответствует паттерну.
   */
  getBookingIdFromUrl(): string {
    const match = this.page.url().match(/\/bookings\/([0-9a-f-]{36})/)
    if (match === null) {
      throw new Error(`No booking id in URL: ${this.page.url()}`)
    }
    return match[1]
  }

  /**
   * Переходит в личный кабинет по кнопке "В кабинет".
   */
  async clickToDashboard(): Promise<void> {
    await this.dashboardBtn.click()
  }
}
