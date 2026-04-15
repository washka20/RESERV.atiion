import { expect, type Locator, type Page } from '@playwright/test'

/**
 * Page Object для страницы каталога услуг и детальной страницы услуги.
 *
 * Локаторы — readonly properties, методы группируют действия и проверки.
 * Все локаторы строятся через getByTestId согласно правилам проекта.
 */
export class CatalogPage {
  readonly page: Page

  readonly container: Locator
  readonly filters: Locator
  readonly searchInput: Locator
  readonly categorySelect: Locator
  readonly typeSelect: Locator
  readonly resetFiltersButton: Locator
  readonly serviceList: Locator
  readonly serviceCards: Locator
  readonly serviceCardLinks: Locator
  readonly emptyState: Locator
  readonly loadingState: Locator

  readonly detailContainer: Locator
  readonly detailLoading: Locator
  readonly detailBookButton: Locator

  constructor(page: Page) {
    this.page = page

    this.container = page.getByTestId('catalog-page')
    this.filters = page.getByTestId('catalog-filters')
    this.searchInput = page.getByTestId('catalog-search-input')
    this.categorySelect = page.getByTestId('catalog-category-filter-select')
    this.typeSelect = page.getByTestId('catalog-type-filter-select')
    this.resetFiltersButton = page.getByTestId('catalog-reset-filters-btn')
    this.serviceList = page.getByTestId('catalog-service-list')
    this.serviceCards = page.getByTestId('catalog-service-card')
    this.serviceCardLinks = page.getByTestId('catalog-service-card-link')
    this.emptyState = page.getByTestId('catalog-service-list-empty')
    this.loadingState = page.getByTestId('catalog-service-list-loading')

    this.detailContainer = page.getByTestId('catalog-service-detail')
    this.detailLoading = page.getByTestId('service-detail-loading')
    this.detailBookButton = page.getByTestId('service-detail-book-btn')
  }

  /**
   * Открывает страницу каталога и ждёт появления первой карточки.
   */
  async goto(): Promise<void> {
    const responsePromise = this.waitForServicesResponse()
    await this.page.goto('/catalog')
    await responsePromise
    await this.expectPageLoaded()
  }

  /**
   * Проверяет что страница каталога загружена: контейнер видим, фильтры
   * отрисованы, отображается хотя бы одна карточка услуги.
   */
  async expectPageLoaded(): Promise<void> {
    await expect(this.container).toBeVisible()
    await expect(this.filters).toBeVisible()
    await expect(this.serviceCards.first()).toBeVisible()
  }

  /**
   * Применяет фильтр по категории по её отображаемому названию.
   * Ждёт нового ответа /api/v1/services после изменения select.
   */
  async filterByCategory(name: string): Promise<void> {
    const responsePromise = this.waitForServicesResponse()
    await this.categorySelect.selectOption({ label: name })
    await responsePromise
  }

  /**
   * Применяет фильтр по типу услуги.
   */
  async filterByType(type: 'time_slot' | 'quantity'): Promise<void> {
    const responsePromise = this.waitForServicesResponse()
    await this.typeSelect.selectOption(type)
    await responsePromise
  }

  /**
   * Заполняет поле поиска. Поиск дебаунсится 300мс — ждём ответ API.
   */
  async search(text: string): Promise<void> {
    const responsePromise = this.waitForServicesResponse()
    await this.searchInput.fill(text)
    await responsePromise
  }

  /**
   * Возвращает текущее количество отображаемых карточек услуг.
   */
  async getServiceCount(): Promise<number> {
    return this.serviceCards.count()
  }

  /**
   * Проверяет ожидаемое количество отрисованных карточек.
   */
  async expectServiceCount(count: number): Promise<void> {
    await expect(this.serviceCards).toHaveCount(count)
  }

  /**
   * Кликает по ссылке "Подробнее" первой карточки и ждёт открытия деталки.
   */
  async openFirstServiceDetail(): Promise<void> {
    await this.serviceCardLinks.first().click()
    await expect(this.detailContainer).toBeVisible()
    await expect(this.detailLoading).toBeHidden()
  }

  /**
   * Проверяет что детальная страница услуги полностью загружена.
   */
  async expectDetailLoaded(): Promise<void> {
    await expect(this.detailContainer).toBeVisible()
    await expect(this.detailBookButton).toBeVisible()
  }

  /**
   * Promise ожидающий следующий ответ /api/v1/services со статусом 200.
   * Используется для синхронизации с асинхронной перезагрузкой списка
   * после применения фильтров.
   */
  private waitForServicesResponse() {
    return this.page.waitForResponse(
      (response) =>
        response.url().includes('/api/v1/services') &&
        !response.url().includes('/api/v1/services/') &&
        response.request().method() === 'GET' &&
        response.status() === 200,
    )
  }
}
