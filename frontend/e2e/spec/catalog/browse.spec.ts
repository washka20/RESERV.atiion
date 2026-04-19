import { expect, test } from '../../fixtures/catalog.fixture'

test.describe('Каталог услуг — browse + filter + detail', () => {
  test.skip(
    !!process.env.CI,
    'TODO: spec ждёт /api/v1/services через waitForResponse — нужен живой backend или HAR-моки (как booking). В CI только Vite preview → таймаут. До фикса catalog.mocks.ts тесты скипаются.',
  )

  test('отображает список услуг', async ({ openedCatalogPage }) => {
    const count = await openedCatalogPage.getServiceCount()
    expect(count).toBeGreaterThan(0)
    await expect(openedCatalogPage.serviceList).toBeVisible()
  })

  test('фильтр по категории сужает список', async ({ openedCatalogPage }) => {
    const initialCount = await openedCatalogPage.getServiceCount()
    expect(initialCount).toBeGreaterThan(0)

    await openedCatalogPage.filterByCategory('Стрижки')

    await expect(openedCatalogPage.serviceCards.first()).toBeVisible()
    const filteredCount = await openedCatalogPage.getServiceCount()
    expect(filteredCount).toBeGreaterThan(0)
    expect(filteredCount).toBeLessThan(initialCount)
  })

  test('поиск фильтрует по названию', async ({ openedCatalogPage }) => {
    const initialCount = await openedCatalogPage.getServiceCount()

    await openedCatalogPage.search('Мужская')

    await expect(openedCatalogPage.serviceCards.first()).toBeVisible()
    const searchedCount = await openedCatalogPage.getServiceCount()
    expect(searchedCount).toBeGreaterThan(0)
    expect(searchedCount).toBeLessThan(initialCount)
  })

  test('клик по карточке открывает детальную страницу', async ({ openedCatalogPage, page }) => {
    await openedCatalogPage.openFirstServiceDetail()
    await openedCatalogPage.expectDetailLoaded()
    await expect(page).toHaveURL(/\/catalog\/[0-9a-f-]{36}/)
  })
})
