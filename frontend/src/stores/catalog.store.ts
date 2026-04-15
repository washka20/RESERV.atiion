import { ref } from 'vue'
import { defineStore } from 'pinia'
import * as catalogApi from '@/api/catalog.api'
import type {
  Category,
  CatalogFilters,
  PaginationMeta,
  Service,
  ServiceListItem,
} from '@/types/catalog.types'

const DEFAULT_PAGINATION: PaginationMeta = {
  total: 0,
  page: 1,
  perPage: 20,
  lastPage: 1,
}

function defaultFilters(): CatalogFilters {
  return {
    categoryId: null,
    subcategoryId: null,
    type: null,
    search: '',
    minPrice: null,
    maxPrice: null,
  }
}

/**
 * Pinia store каталога услуг.
 *
 * Управляет списком услуг, активной услугой, категориями, фильтрами и пагинацией.
 * API ошибки сохраняются в `error`, состояние загрузки — в `isLoading`.
 */
export const useCatalogStore = defineStore('catalog', () => {
  const services = ref<ServiceListItem[]>([])
  const currentService = ref<Service | null>(null)
  const categories = ref<Category[]>([])
  const filters = ref<CatalogFilters>(defaultFilters())
  const pagination = ref<PaginationMeta>({ ...DEFAULT_PAGINATION })
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  function extractMessage(err: unknown): string {
    if (err instanceof Error) return err.message
    return 'Unknown error'
  }

  /** Загружает список услуг по текущим фильтрам и странице. */
  async function fetchServices(): Promise<void> {
    isLoading.value = true
    error.value = null
    try {
      const envelope = await catalogApi.listServices({
        categoryId: filters.value.categoryId,
        subcategoryId: filters.value.subcategoryId,
        type: filters.value.type,
        search: filters.value.search,
        minPrice: filters.value.minPrice,
        maxPrice: filters.value.maxPrice,
        page: pagination.value.page,
        perPage: pagination.value.perPage,
      })
      services.value = envelope.data ?? []
      if (envelope.meta) {
        pagination.value = envelope.meta
      }
    } catch (err) {
      error.value = extractMessage(err)
      services.value = []
    } finally {
      isLoading.value = false
    }
  }

  /** Загружает полную карточку услуги. */
  async function fetchService(id: string): Promise<void> {
    isLoading.value = true
    error.value = null
    try {
      const envelope = await catalogApi.getService(id)
      currentService.value = envelope.data
    } catch (err) {
      error.value = extractMessage(err)
      currentService.value = null
    } finally {
      isLoading.value = false
    }
  }

  /** Загружает дерево категорий (вызывать один раз при инициализации приложения). */
  async function fetchCategories(): Promise<void> {
    error.value = null
    try {
      const envelope = await catalogApi.listCategories()
      categories.value = envelope.data ?? []
    } catch (err) {
      error.value = extractMessage(err)
      categories.value = []
    }
  }

  /**
   * Применяет частичный патч фильтров и сбрасывает страницу на первую.
   *
   * @param patch поля фильтров для обновления
   */
  function setFilters(patch: Partial<CatalogFilters>): void {
    filters.value = { ...filters.value, ...patch }
    pagination.value = { ...pagination.value, page: 1 }
  }

  /** Сбрасывает фильтры в значения по умолчанию и страницу на первую. */
  function resetFilters(): void {
    filters.value = defaultFilters()
    pagination.value = { ...pagination.value, page: 1 }
  }

  /**
   * Меняет номер текущей страницы.
   *
   * @param page новый номер страницы (1-based)
   */
  function setPage(page: number): void {
    pagination.value = { ...pagination.value, page }
  }

  return {
    services,
    currentService,
    categories,
    filters,
    pagination,
    isLoading,
    error,
    fetchServices,
    fetchService,
    fetchCategories,
    setFilters,
    resetFilters,
    setPage,
  }
})
