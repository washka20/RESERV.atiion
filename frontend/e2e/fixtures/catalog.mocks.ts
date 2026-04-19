import type { Page, Route } from '@playwright/test'

/**
 * Билдеры для API-моков E2E тестов каталога.
 *
 * Все помощники возвращают raw JSON в snake_case — точно так как шлёт
 * Laravel backend `/api/v1/services` и `/api/v1/categories`. Маппинг в
 * camelCase делает `api/catalog.api.ts`.
 *
 * Axios в api/catalog.api.ts шлёт query-параметры в camelCase (`categoryId`,
 * `perPage`, `search`) — без преобразования в snake_case. Моки ниже
 * ориентируются именно на camelCase форму.
 */

export interface CatalogServiceFixture {
  id: string
  name: string
  priceAmount: number
  priceCurrency: 'RUB' | 'USD' | 'EUR'
  type: 'time_slot' | 'quantity'
  categoryId: string
  categoryName: string
  subcategoryId: string | null
  subcategoryName: string | null
  primaryImage: string | null
  description: string
  durationMinutes: number | null
  totalQuantity: number | null
}

export interface CatalogCategoryFixture {
  id: string
  name: string
  slug: string
  sortOrder: number
}

const CATEGORY_HAIRCUTS: CatalogCategoryFixture = {
  id: '11111111-1111-4111-8111-111111111111',
  name: 'Стрижки',
  slug: 'haircuts',
  sortOrder: 1,
}

const CATEGORY_MASSAGE: CatalogCategoryFixture = {
  id: '22222222-2222-4222-8222-222222222222',
  name: 'Массаж',
  slug: 'massage',
  sortOrder: 2,
}

/**
 * Набор дефолтных услуг для каталога.
 *
 * Содержит 5 услуг из двух категорий: 3 «Стрижки» (2 с ключом "Мужская"
 * в названии) + 2 «Массаж». Цены в копейках.
 */
export const DEFAULT_SERVICES: CatalogServiceFixture[] = [
  {
    id: 'aaaaaaa1-aaaa-4aaa-8aaa-aaaaaaaaaaa1',
    name: 'Мужская классическая стрижка',
    priceAmount: 150000,
    priceCurrency: 'RUB',
    type: 'time_slot',
    categoryId: CATEGORY_HAIRCUTS.id,
    categoryName: CATEGORY_HAIRCUTS.name,
    subcategoryId: null,
    subcategoryName: null,
    primaryImage: null,
    description: 'Классическая мужская стрижка с укладкой',
    durationMinutes: 45,
    totalQuantity: null,
  },
  {
    id: 'aaaaaaa2-aaaa-4aaa-8aaa-aaaaaaaaaaa2',
    name: 'Мужская стрижка с бородой',
    priceAmount: 200000,
    priceCurrency: 'RUB',
    type: 'time_slot',
    categoryId: CATEGORY_HAIRCUTS.id,
    categoryName: CATEGORY_HAIRCUTS.name,
    subcategoryId: null,
    subcategoryName: null,
    primaryImage: null,
    description: 'Стрижка + моделирование бороды',
    durationMinutes: 60,
    totalQuantity: null,
  },
  {
    id: 'aaaaaaa3-aaaa-4aaa-8aaa-aaaaaaaaaaa3',
    name: 'Женская стрижка',
    priceAmount: 250000,
    priceCurrency: 'RUB',
    type: 'time_slot',
    categoryId: CATEGORY_HAIRCUTS.id,
    categoryName: CATEGORY_HAIRCUTS.name,
    subcategoryId: null,
    subcategoryName: null,
    primaryImage: null,
    description: 'Женская стрижка любой длины',
    durationMinutes: 60,
    totalQuantity: null,
  },
  {
    id: 'bbbbbbb1-bbbb-4bbb-8bbb-bbbbbbbbbbb1',
    name: 'Расслабляющий массаж спины',
    priceAmount: 300000,
    priceCurrency: 'RUB',
    type: 'time_slot',
    categoryId: CATEGORY_MASSAGE.id,
    categoryName: CATEGORY_MASSAGE.name,
    subcategoryId: null,
    subcategoryName: null,
    primaryImage: null,
    description: 'Классический расслабляющий массаж',
    durationMinutes: 60,
    totalQuantity: null,
  },
  {
    id: 'bbbbbbb2-bbbb-4bbb-8bbb-bbbbbbbbbbb2',
    name: 'Тайский массаж',
    priceAmount: 400000,
    priceCurrency: 'RUB',
    type: 'time_slot',
    categoryId: CATEGORY_MASSAGE.id,
    categoryName: CATEGORY_MASSAGE.name,
    subcategoryId: null,
    subcategoryName: null,
    primaryImage: null,
    description: 'Глубокий тайский массаж',
    durationMinutes: 90,
    totalQuantity: null,
  },
]

export const DEFAULT_CATEGORIES: CatalogCategoryFixture[] = [
  CATEGORY_HAIRCUTS,
  CATEGORY_MASSAGE,
]

function toListItemJson(svc: CatalogServiceFixture): Record<string, unknown> {
  return {
    id: svc.id,
    name: svc.name,
    price_amount: svc.priceAmount,
    price_currency: svc.priceCurrency,
    type: svc.type,
    category_name: svc.categoryName,
    subcategory_name: svc.subcategoryName,
    primary_image: svc.primaryImage,
    is_active: true,
  }
}

function toServiceDetailJson(svc: CatalogServiceFixture): Record<string, unknown> {
  return {
    id: svc.id,
    name: svc.name,
    description: svc.description,
    price_amount: svc.priceAmount,
    price_currency: svc.priceCurrency,
    type: svc.type,
    duration_minutes: svc.durationMinutes,
    total_quantity: svc.totalQuantity,
    category_id: svc.categoryId,
    category_name: svc.categoryName,
    subcategory_id: svc.subcategoryId,
    subcategory_name: svc.subcategoryName,
    is_active: true,
    images: [],
    created_at: '2026-04-01T10:00:00+00:00',
    updated_at: '2026-04-01T10:00:00+00:00',
  }
}

function toCategoryJson(cat: CatalogCategoryFixture): Record<string, unknown> {
  return {
    id: cat.id,
    name: cat.name,
    slug: cat.slug,
    sort_order: cat.sortOrder,
    subcategories: [],
  }
}

function servicesListEnvelope(services: CatalogServiceFixture[], page: number, perPage: number): object {
  return {
    success: true,
    data: services.map(toListItemJson),
    error: null,
    meta: {
      total: services.length,
      page,
      per_page: perPage,
      last_page: Math.max(1, Math.ceil(services.length / perPage)),
    },
  }
}

function categoriesEnvelope(categories: CatalogCategoryFixture[]): object {
  return {
    success: true,
    data: categories.map(toCategoryJson),
    error: null,
    meta: null,
  }
}

function serviceDetailEnvelope(svc: CatalogServiceFixture): object {
  return {
    success: true,
    data: toServiceDetailJson(svc),
    error: null,
    meta: null,
  }
}

function notFoundEnvelope(id: string): object {
  return {
    success: false,
    data: null,
    error: {
      code: 'SERVICE_NOT_FOUND',
      message: `Service ${id} not found`,
      details: null,
    },
    meta: null,
  }
}

/**
 * Фильтрует услуги по query-параметрам из URL.
 *
 * Поддерживает `categoryId`, `type`, `search` (case-insensitive подстрока
 * в `name`). Страница+perPage для расчёта meta берутся из query либо
 * дефолтов (page=1, perPage=20).
 */
function filterServices(
  all: CatalogServiceFixture[],
  search: URLSearchParams,
): { items: CatalogServiceFixture[]; page: number; perPage: number } {
  const categoryId = search.get('categoryId')
  const type = search.get('type')
  const query = (search.get('search') ?? '').trim().toLowerCase()
  const page = Number(search.get('page') ?? '1') || 1
  const perPage = Number(search.get('perPage') ?? '20') || 20

  const items = all.filter((svc) => {
    if (categoryId !== null && categoryId !== '' && svc.categoryId !== categoryId) return false
    if (type !== null && type !== '' && svc.type !== type) return false
    if (query.length > 0 && !svc.name.toLowerCase().includes(query)) return false
    return true
  })

  return { items, page, perPage }
}

/**
 * Регистрирует полный набор моков каталога: список, детали, категории.
 *
 * Моки реагируют на query-параметры: фильтрация по `categoryId`, `type`,
 * `search`. `GET /services/{uuid}` отдаёт карточку либо 404.
 * `GET /categories` отдаёт 2 дефолтные категории.
 *
 * @param page Playwright Page
 * @param services список услуг (по умолчанию DEFAULT_SERVICES)
 * @param categories список категорий (по умолчанию DEFAULT_CATEGORIES)
 */
export async function setupCatalogMocks(
  page: Page,
  services: CatalogServiceFixture[] = DEFAULT_SERVICES,
  categories: CatalogCategoryFixture[] = DEFAULT_CATEGORIES,
): Promise<void> {
  await page.route('**/api/v1/services?**', async (route: Route) => {
    if (route.request().method() !== 'GET') {
      await route.fallback()
      return
    }
    const url = new URL(route.request().url())
    const { items, page: pageNum, perPage } = filterServices(services, url.searchParams)
    await route.fulfill({ status: 200, json: servicesListEnvelope(items, pageNum, perPage) })
  })

  await page.route('**/api/v1/services', async (route: Route) => {
    if (route.request().method() !== 'GET') {
      await route.fallback()
      return
    }
    await route.fulfill({ status: 200, json: servicesListEnvelope(services, 1, 20) })
  })

  await page.route(/\/api\/v1\/services\/[0-9a-f-]{36}(?!\/)/, async (route: Route) => {
    if (route.request().method() !== 'GET') {
      await route.fallback()
      return
    }
    const url = route.request().url()
    const match = url.match(/\/services\/([0-9a-f-]{36})/)
    const id = match?.[1] ?? ''
    const svc = services.find((s) => s.id === id)
    if (svc === undefined) {
      await route.fulfill({ status: 404, json: notFoundEnvelope(id) })
      return
    }
    await route.fulfill({ status: 200, json: serviceDetailEnvelope(svc) })
  })

  await page.route('**/api/v1/categories', async (route: Route) => {
    if (route.request().method() !== 'GET') {
      await route.fallback()
      return
    }
    await route.fulfill({ status: 200, json: categoriesEnvelope(categories) })
  })
}
