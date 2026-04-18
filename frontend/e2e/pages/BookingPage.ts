import { expect, type Locator, type Page } from '@playwright/test'

/**
 * Page Object для `/book/:serviceId` — формы создания бронирования.
 *
 * Поддерживает оба сценария услуги: TIME_SLOT (выбор даты + слота) и QUANTITY
 * (диапазон дат + количество). Локаторы — readonly properties, методы
 * группируют действия и переиспользуемые проверки. Все локаторы строятся
 * через `getByTestId` согласно правилам проекта.
 */
export class BookingPage {
  readonly page: Page

  readonly container: Locator
  readonly form: Locator
  readonly submitBtn: Locator
  readonly notesInput: Locator
  readonly errorEl: Locator
  readonly summary: Locator

  readonly dateInput: Locator
  readonly slotButtons: Locator
  readonly noSlots: Locator

  readonly checkInInput: Locator
  readonly checkOutInput: Locator
  readonly quantityInput: Locator
  readonly availabilityInfo: Locator

  constructor(page: Page) {
    this.page = page

    this.container = page.getByTestId('booking-page')
    this.form = page.getByTestId('booking-form')
    this.submitBtn = page.getByTestId('booking-submit-btn')
    this.notesInput = page.getByTestId('booking-notes-input')
    this.errorEl = page.getByTestId('booking-error')
    this.summary = page.getByTestId('booking-summary')

    this.dateInput = page.getByTestId('booking-date-input')
    this.slotButtons = page.getByTestId('booking-slot-btn')
    this.noSlots = page.getByTestId('booking-no-slots')

    this.checkInInput = page.getByTestId('booking-date-checkin-input')
    this.checkOutInput = page.getByTestId('booking-date-checkout-input')
    this.quantityInput = page.getByTestId('booking-quantity-input')
    this.availabilityInfo = page.getByTestId('booking-availability-info')
  }

  /**
   * Открывает страницу бронирования услуги и ждёт появления формы.
   */
  async goto(serviceId: string): Promise<void> {
    await this.page.goto(`/book/${serviceId}`)
    await this.expectPageLoaded()
  }

  /**
   * Проверяет что форма отрисована — контейнер страницы и форма видимы.
   */
  async expectPageLoaded(): Promise<void> {
    await expect(this.container).toBeVisible()
    await expect(this.form).toBeVisible()
  }

  /**
   * Заполняет поле даты (TIME_SLOT). Формат `YYYY-MM-DD`.
   */
  async selectDate(date: string): Promise<void> {
    await this.dateInput.fill(date)
  }

  /**
   * Возвращает локатор кнопки слота по его идентификатору.
   * Используется для выбора конкретного слота из списка.
   */
  slotBySlotId(slotId: string): Locator {
    return this.page.locator(
      `[data-test-id="booking-slot-btn"][data-slot-id="${slotId}"]`,
    )
  }

  /**
   * Кликает по первому доступному слоту.
   * Использовать только когда порядок слотов детерминирован (моки).
   */
  async selectFirstSlot(): Promise<void> {
    await this.slotButtons.first().click()
  }

  /**
   * Заполняет диапазон дат для QUANTITY услуги.
   * Формат дат — `YYYY-MM-DD`.
   */
  async setDateRange(checkIn: string, checkOut: string): Promise<void> {
    await this.checkInInput.fill(checkIn)
    await this.checkOutInput.fill(checkOut)
  }

  /**
   * Заполняет поле количества для QUANTITY услуги.
   */
  async setQuantity(count: number): Promise<void> {
    await this.quantityInput.fill(String(count))
  }

  /**
   * Заполняет поле комментария к бронированию.
   */
  async fillNotes(text: string): Promise<void> {
    await this.notesInput.fill(text)
  }

  /**
   * Отправляет форму бронирования. Клик по submit-кнопке.
   */
  async submit(): Promise<void> {
    await this.submitBtn.click()
  }

  /**
   * Проверяет что после успешной отправки формы браузер перешёл
   * на страницу подтверждения `/bookings/:uuid`.
   */
  async expectRedirectToConfirm(): Promise<void> {
    await expect(this.page).toHaveURL(/\/bookings\/[0-9a-f-]{36}/)
  }

  /**
   * Проверяет что в форме отображается ошибка с заданным текстом.
   */
  async expectError(text: string | RegExp): Promise<void> {
    await expect(this.errorEl).toBeVisible()
    await expect(this.errorEl).toContainText(text)
  }

  /**
   * Проверяет что submit-кнопка выключена (canSubmit=false).
   */
  async expectSubmitDisabled(): Promise<void> {
    await expect(this.submitBtn).toBeDisabled()
  }
}
