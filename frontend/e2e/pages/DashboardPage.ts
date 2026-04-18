import { expect, type Locator, type Page } from '@playwright/test'

/**
 * Page Object для страницы кабинета `/dashboard` — список бронирований
 * текущего пользователя с фильтрацией по статусу и действием отмены.
 */
export class DashboardPage {
  readonly page: Page

  readonly container: Locator
  readonly filterStatus: Locator
  readonly emptyState: Locator
  readonly loadingState: Locator
  readonly errorEl: Locator
  readonly cards: Locator

  constructor(page: Page) {
    this.page = page

    this.container = page.getByTestId('dashboard-page')
    this.filterStatus = page.getByTestId('dashboard-filter-status')
    this.emptyState = page.getByTestId('dashboard-empty')
    this.loadingState = page.getByTestId('dashboard-loading')
    this.errorEl = page.getByTestId('dashboard-error')
    this.cards = page.getByTestId('dashboard-booking-card')
  }

  /**
   * Открывает страницу кабинета и ждёт завершения загрузки.
   */
  async goto(): Promise<void> {
    await this.page.goto('/dashboard')
    await this.expectPageLoaded()
  }

  /**
   * Проверяет что контейнер дашборда отрисован.
   */
  async expectPageLoaded(): Promise<void> {
    await expect(this.container).toBeVisible()
  }

  /**
   * Возвращает локатор карточки бронирования по его UUID.
   */
  bookingCard(bookingId: string): Locator {
    return this.page.locator(
      `[data-test-id="dashboard-booking-card"][data-booking-id="${bookingId}"]`,
    )
  }

  /**
   * Возвращает локатор кнопки отмены внутри карточки бронирования.
   */
  cancelButton(bookingId: string): Locator {
    return this.bookingCard(bookingId).getByTestId('dashboard-booking-cancel-btn')
  }

  /**
   * Возвращает локатор бейджа статуса внутри карточки бронирования.
   */
  statusBadge(bookingId: string): Locator {
    return this.bookingCard(bookingId).getByTestId('dashboard-booking-status')
  }

  /**
   * Кликает по кнопке отмены конкретного бронирования.
   */
  async cancel(bookingId: string): Promise<void> {
    await this.cancelButton(bookingId).click()
  }

  /**
   * Фильтрует список по статусу. Значение `all` — сброс фильтра.
   */
  async filterByStatus(status: 'all' | 'pending' | 'confirmed' | 'cancelled' | 'completed'): Promise<void> {
    await this.filterStatus.selectOption(status)
  }
}
