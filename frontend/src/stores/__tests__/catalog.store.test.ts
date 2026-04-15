import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { useCatalogStore } from '@/stores/catalog.store'
import type {
  ApiEnvelope,
  Category,
  Service,
  ServiceListItem,
} from '@/types/catalog.types'

vi.mock('@/api/catalog.api', () => ({
  listServices: vi.fn(),
  getService: vi.fn(),
  listCategories: vi.fn(),
  getCategoryBySlug: vi.fn(),
}))

import * as catalogApi from '@/api/catalog.api'

const mockedListServices = vi.mocked(catalogApi.listServices)
const mockedGetService = vi.mocked(catalogApi.getService)
const mockedListCategories = vi.mocked(catalogApi.listCategories)

const sampleListItem: ServiceListItem = {
  id: '11111111-1111-1111-1111-111111111111',
  name: 'Стрижка',
  priceAmount: 150000,
  priceCurrency: 'RUB',
  type: 'time_slot',
  categoryName: 'Стрижки',
  subcategoryName: 'Мужские',
  primaryImage: null,
  isActive: true,
}

const sampleService: Service = {
  ...sampleListItem,
  description: 'desc',
  durationMinutes: 45,
  totalQuantity: null,
  categoryId: 'cat-id',
  subcategoryId: 'sub-id',
  images: [],
  createdAt: '2026-04-15T20:00:00+00:00',
  updatedAt: '2026-04-15T20:00:00+00:00',
}

const sampleCategory: Category = {
  id: 'cat-id',
  name: 'Стрижки',
  slug: 'haircuts',
  sortOrder: 10,
  subcategories: [],
}

function envelopeOk<T>(data: T, meta: ApiEnvelope<T>['meta'] = null): ApiEnvelope<T> {
  return { success: true, data, error: null, meta }
}

describe('useCatalogStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('fetchServices обновляет state и pagination из envelope', async () => {
    mockedListServices.mockResolvedValueOnce(
      envelopeOk([sampleListItem], { total: 1, page: 2, perPage: 5, lastPage: 1 }),
    )

    const store = useCatalogStore()
    store.setPage(2)
    await store.fetchServices()

    expect(store.services).toEqual([sampleListItem])
    expect(store.pagination).toEqual({ total: 1, page: 2, perPage: 5, lastPage: 1 })
    expect(store.isLoading).toBe(false)
    expect(store.error).toBeNull()
    expect(mockedListServices).toHaveBeenCalledWith(
      expect.objectContaining({ page: 2, perPage: 20 }),
    )
  })

  it('setFilters мерджит патч и сбрасывает страницу на 1', () => {
    const store = useCatalogStore()
    store.setPage(5)
    expect(store.pagination.page).toBe(5)

    store.setFilters({ categoryId: 'abc', search: 'cut' })

    expect(store.filters.categoryId).toBe('abc')
    expect(store.filters.search).toBe('cut')
    expect(store.filters.subcategoryId).toBeNull()
    expect(store.pagination.page).toBe(1)
  })

  it('resetFilters восстанавливает дефолты и сбрасывает страницу', () => {
    const store = useCatalogStore()
    store.setFilters({ categoryId: 'abc', minPrice: 100 })
    store.setPage(3)

    store.resetFilters()

    expect(store.filters.categoryId).toBeNull()
    expect(store.filters.minPrice).toBeNull()
    expect(store.filters.search).toBe('')
    expect(store.pagination.page).toBe(1)
  })

  it('fetchServices устанавливает error и очищает services при ошибке api', async () => {
    mockedListServices.mockRejectedValueOnce(new Error('Network down'))

    const store = useCatalogStore()
    store.services = [sampleListItem]
    await store.fetchServices()

    expect(store.error).toBe('Network down')
    expect(store.services).toEqual([])
    expect(store.isLoading).toBe(false)
  })

  it('fetchService сохраняет currentService из envelope', async () => {
    mockedGetService.mockResolvedValueOnce(envelopeOk(sampleService))

    const store = useCatalogStore()
    await store.fetchService(sampleService.id)

    expect(store.currentService).toEqual(sampleService)
    expect(store.error).toBeNull()
  })

  it('fetchCategories обновляет список категорий', async () => {
    mockedListCategories.mockResolvedValueOnce(envelopeOk([sampleCategory]))

    const store = useCatalogStore()
    await store.fetchCategories()

    expect(store.categories).toEqual([sampleCategory])
    expect(store.error).toBeNull()
  })
})
