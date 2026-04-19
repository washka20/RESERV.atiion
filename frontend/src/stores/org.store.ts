import { ref } from 'vue'
import { defineStore } from 'pinia'
import * as orgApi from '@/api/org.api'

/**
 * Статистика organization dashboard (placeholder shape до backend endpoints).
 */
export interface OrgStats {
  totalBookings: number
  pendingBookings: number
  confirmedBookings: number
  revenue: number
}

function extractMessage(err: unknown, fallback: string): string {
  if (err && typeof err === 'object' && 'response' in err) {
    const res = (err as {
      response?: { status?: number; data?: { error?: { message?: string } } }
    }).response
    const envMsg = res?.data?.error?.message
    if (envMsg) return envMsg
    const status = res?.status
    if (status === 401) return 'Требуется вход в систему'
    if (status === 403) return 'Недостаточно прав'
    if (status === 404) return 'Организация не найдена'
    if (status && status >= 500) return 'Ошибка сервера — попробуйте позже'
  }
  if (err instanceof Error) {
    if (/^Request failed with status code/.test(err.message)) return fallback
    return err.message
  }
  return fallback
}

/**
 * Pinia store organization-кабинета.
 *
 * Хранит active org slug (из URL) и dashboard stats. Membership сам по себе
 * живёт в auth.store — здесь дублировать не нужно.
 */
export const useOrgStore = defineStore('org', () => {
  const activeOrgSlug = ref<string | null>(null)
  const orgStats = ref<OrgStats | null>(null)
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  /**
   * Устанавливает slug активной organization. Обычно вызывается из router
   * при входе в /o/{slug}/* routes.
   */
  function setActive(slug: string): void {
    activeOrgSlug.value = slug
  }

  /** Сбрасывает active org state — для logout или возврата в Personal. */
  function clearActive(): void {
    activeOrgSlug.value = null
    orgStats.value = null
    error.value = null
  }

  /**
   * Загружает dashboard-статистику organization.
   *
   * Backend endpoint `/organizations/{slug}/stats` пока stub — возвращает
   * null если 404. Ошибки extracted через extractMessage.
   */
  async function loadStats(slug: string): Promise<void> {
    isLoading.value = true
    error.value = null
    try {
      const envelope = await orgApi.getStats(slug)
      orgStats.value = envelope.success && envelope.data ? envelope.data : null
    } catch (err) {
      error.value = extractMessage(err, 'Не удалось загрузить статистику')
      orgStats.value = null
      throw err
    } finally {
      isLoading.value = false
    }
  }

  return {
    activeOrgSlug,
    orgStats,
    isLoading,
    error,
    setActive,
    clearActive,
    loadStats,
  }
})
