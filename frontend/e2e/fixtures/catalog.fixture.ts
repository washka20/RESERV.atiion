import { test as base } from '@playwright/test'
import { CatalogPage } from '../pages/CatalogPage'
import { setupCatalogMocks } from './catalog.mocks'

/**
 * Фикстуры каталога:
 * - `catalogPage` — bare POM без навигации, используется для тестов
 *   которые сами решают когда переходить на страницу.
 * - `openedCatalogPage` — POM с уже выполненным переходом и проверкой
 *   загрузки страницы (контейнер + первая карточка видимы).
 *
 * Auto-fixture `mockApi` регистрирует inline-моки `/api/v1/services` и
 * `/api/v1/categories` перед каждым тестом. Живой backend в E2E не
 * используется — моки те же, что в спеках бронирования.
 */
type CatalogFixtures = {
  catalogPage: CatalogPage
  openedCatalogPage: CatalogPage
  mockApi: void
}

export const test = base.extend<CatalogFixtures>({
  mockApi: [
    async ({ page }, use) => {
      await setupCatalogMocks(page)
      await use()
    },
    { auto: true },
  ],

  catalogPage: async ({ page }, use) => {
    const catalogPage = new CatalogPage(page)
    await use(catalogPage)
  },

  openedCatalogPage: async ({ page }, use) => {
    const catalogPage = new CatalogPage(page)
    await catalogPage.goto()
    await use(catalogPage)
  },
})

export { expect } from '@playwright/test'
