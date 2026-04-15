/**
 * Доменные типы каталога услуг.
 *
 * Соответствуют JSON-схеме публичного API `/api/v1/services` и `/api/v1/categories`.
 * Backend возвращает поля в snake_case — конвертация в camelCase делается в api/catalog.api.ts.
 */

/** Тип услуги: бронирование конкретного слота времени или количества единиц. */
export type ServiceType = 'time_slot' | 'quantity'

/** Поддерживаемые валюты. */
export type Currency = 'RUB' | 'USD' | 'EUR'

/** Элемент списка услуг (compact view для каталога). */
export interface ServiceListItem {
  id: string
  name: string
  /** Цена в минимальных единицах валюты (копейках/центах). */
  priceAmount: number
  priceCurrency: Currency
  type: ServiceType
  categoryName: string
  subcategoryName: string | null
  primaryImage: string | null
  isActive: boolean
}

/** Полная карточка услуги (detail view). */
export interface Service {
  id: string
  name: string
  description: string
  priceAmount: number
  priceCurrency: Currency
  type: ServiceType
  /** Длительность для type=time_slot. */
  durationMinutes: number | null
  /** Общее количество для type=quantity. */
  totalQuantity: number | null
  categoryId: string
  categoryName: string
  subcategoryId: string | null
  subcategoryName: string | null
  isActive: boolean
  images: string[]
  createdAt: string
  updatedAt: string
}

/** Подкатегория внутри категории. */
export interface Subcategory {
  id: string
  name: string
  slug: string
  sortOrder: number
}

/** Категория с вложенными подкатегориями. */
export interface Category {
  id: string
  name: string
  slug: string
  sortOrder: number
  subcategories: Subcategory[]
}

/** Фильтры выбора услуг в каталоге. */
export interface CatalogFilters {
  categoryId: string | null
  subcategoryId: string | null
  type: ServiceType | null
  search: string
  minPrice: number | null
  maxPrice: number | null
}

/** Метаданные пагинации списка. */
export interface PaginationMeta {
  total: number
  page: number
  perPage: number
  lastPage: number
}

/** Структура ошибки внутри envelope. */
export interface ApiError {
  code: string
  message: string
  details?: Record<string, unknown> | null
}

/**
 * Универсальный envelope ответа API.
 *
 * `data` присутствует при `success=true`, `error` — при `success=false`.
 * `meta` отдаётся для коллекций с пагинацией.
 */
export interface ApiEnvelope<T> {
  success: boolean
  data: T | null
  error: ApiError | null
  meta: PaginationMeta | null
}
