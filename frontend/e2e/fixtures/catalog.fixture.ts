import { test as base } from '@playwright/test'
import { CatalogPage } from '../pages/CatalogPage'

/**
 * Фикстуры каталога:
 * - `catalogPage` — bare POM без навигации, используется для тестов
 *   которые сами решают когда переходить на страницу.
 * - `openedCatalogPage` — POM с уже выполненным переходом и проверкой
 *   загрузки страницы (контейнер + первая карточка видимы).
 */
type CatalogFixtures = {
  catalogPage: CatalogPage
  openedCatalogPage: CatalogPage
}

export const test = base.extend<CatalogFixtures>({
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
