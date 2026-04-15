import { apiClient } from './client'
import type {
  ApiEnvelope,
  Category,
  CatalogFilters,
  PaginationMeta,
  Service,
  ServiceListItem,
  Subcategory,
} from '@/types/catalog.types'

interface RawServiceListItem {
  id: string
  name: string
  price_amount: number
  price_currency: 'RUB' | 'USD' | 'EUR'
  type: 'time_slot' | 'quantity'
  category_name: string
  subcategory_name: string | null
  primary_image: string | null
  is_active: boolean
}

interface RawService {
  id: string
  name: string
  description: string
  price_amount: number
  price_currency: 'RUB' | 'USD' | 'EUR'
  type: 'time_slot' | 'quantity'
  duration_minutes: number | null
  total_quantity: number | null
  category_id: string
  category_name: string
  subcategory_id: string | null
  subcategory_name: string | null
  is_active: boolean
  images: string[]
  created_at: string
  updated_at: string
}

interface RawSubcategory {
  id: string
  name: string
  slug: string
  sort_order: number
}

interface RawCategory {
  id: string
  name: string
  slug: string
  sort_order: number
  subcategories: RawSubcategory[]
}

interface RawPaginationMeta {
  total: number
  page: number
  per_page: number
  last_page: number
}

interface RawApiEnvelope<T> {
  success: boolean
  data: T | null
  error: { code: string; message: string; details?: Record<string, unknown> | null } | null
  meta: RawPaginationMeta | null
}

function mapServiceListItem(raw: RawServiceListItem): ServiceListItem {
  return {
    id: raw.id,
    name: raw.name,
    priceAmount: raw.price_amount,
    priceCurrency: raw.price_currency,
    type: raw.type,
    categoryName: raw.category_name,
    subcategoryName: raw.subcategory_name,
    primaryImage: raw.primary_image,
    isActive: raw.is_active,
  }
}

function mapService(raw: RawService): Service {
  return {
    id: raw.id,
    name: raw.name,
    description: raw.description,
    priceAmount: raw.price_amount,
    priceCurrency: raw.price_currency,
    type: raw.type,
    durationMinutes: raw.duration_minutes,
    totalQuantity: raw.total_quantity,
    categoryId: raw.category_id,
    categoryName: raw.category_name,
    subcategoryId: raw.subcategory_id,
    subcategoryName: raw.subcategory_name,
    isActive: raw.is_active,
    images: raw.images,
    createdAt: raw.created_at,
    updatedAt: raw.updated_at,
  }
}

function mapSubcategory(raw: RawSubcategory): Subcategory {
  return {
    id: raw.id,
    name: raw.name,
    slug: raw.slug,
    sortOrder: raw.sort_order,
  }
}

function mapCategory(raw: RawCategory): Category {
  return {
    id: raw.id,
    name: raw.name,
    slug: raw.slug,
    sortOrder: raw.sort_order,
    subcategories: raw.subcategories.map(mapSubcategory),
  }
}

function mapPaginationMeta(raw: RawPaginationMeta | null): PaginationMeta | null {
  if (raw === null) {
    return null
  }
  return {
    total: raw.total,
    page: raw.page,
    perPage: raw.per_page,
    lastPage: raw.last_page,
  }
}

function mapEnvelope<TRaw, T>(
  raw: RawApiEnvelope<TRaw>,
  mapData: (data: TRaw) => T,
): ApiEnvelope<T> {
  return {
    success: raw.success,
    data: raw.data === null ? null : mapData(raw.data),
    error: raw.error,
    meta: mapPaginationMeta(raw.meta),
  }
}

/** Параметры запроса списка услуг. */
export interface ListServicesParams extends Partial<CatalogFilters> {
  page?: number
  perPage?: number
}

/**
 * Сериализует параметры в query string, отбрасывая `null`/`undefined`/пустые строки.
 */
function buildQueryParams(params: ListServicesParams): Record<string, string | number> {
  const result: Record<string, string | number> = {}
  if (params.categoryId) result.categoryId = params.categoryId
  if (params.subcategoryId) result.subcategoryId = params.subcategoryId
  if (params.type) result.type = params.type
  if (params.search && params.search.length > 0) result.search = params.search
  if (typeof params.minPrice === 'number') result.minPrice = params.minPrice
  if (typeof params.maxPrice === 'number') result.maxPrice = params.maxPrice
  if (typeof params.page === 'number') result.page = params.page
  if (typeof params.perPage === 'number') result.perPage = params.perPage
  return result
}

/**
 * Получить список услуг каталога с фильтрами и пагинацией.
 *
 * @param params фильтры и параметры пагинации
 * @returns envelope со списком услуг и meta пагинации
 */
export async function listServices(
  params: ListServicesParams = {},
): Promise<ApiEnvelope<ServiceListItem[]>> {
  const response = await apiClient.get<RawApiEnvelope<RawServiceListItem[]>>('/services', {
    params: buildQueryParams(params),
  })
  return mapEnvelope(response.data, (items) => items.map(mapServiceListItem))
}

/**
 * Получить услугу по идентификатору.
 *
 * @param id UUID услуги
 * @returns envelope с полной карточкой услуги
 */
export async function getService(id: string): Promise<ApiEnvelope<Service>> {
  const response = await apiClient.get<RawApiEnvelope<RawService>>(`/services/${id}`)
  return mapEnvelope(response.data, mapService)
}

/**
 * Получить дерево категорий с подкатегориями.
 *
 * @returns envelope со списком категорий
 */
export async function listCategories(): Promise<ApiEnvelope<Category[]>> {
  const response = await apiClient.get<RawApiEnvelope<RawCategory[]>>('/categories')
  return mapEnvelope(response.data, (items) => items.map(mapCategory))
}

/**
 * Получить категорию по slug.
 *
 * @param slug slug категории
 * @returns envelope с категорией и её подкатегориями
 */
export async function getCategoryBySlug(slug: string): Promise<ApiEnvelope<Category>> {
  const response = await apiClient.get<RawApiEnvelope<RawCategory>>(`/categories/${slug}`)
  return mapEnvelope(response.data, mapCategory)
}
