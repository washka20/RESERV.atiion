import { ref } from 'vue'
import { defineStore } from 'pinia'
import * as payoutsApi from '@/api/payouts.api'

/**
 * Транзакция выплаты organization.
 */
export interface PayoutTransaction {
  id: string
  amount: number
  currency: string
  status: 'pending' | 'completed' | 'failed'
  createdAt: string
  description: string | null
}

/**
 * Настройки выплат organization (placeholder shape).
 */
export interface PayoutSettings {
  bankAccount: string | null
  iban: string | null
  accountHolder: string | null
  autoPayoutEnabled: boolean
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
    if (status === 404) return 'Не найдено'
    if (status === 422) return 'Проверьте введённые данные'
    if (status && status >= 500) return 'Ошибка сервера — попробуйте позже'
  }
  if (err instanceof Error) {
    if (/^Request failed with status code/.test(err.message)) return fallback
    return err.message
  }
  return fallback
}

/**
 * Pinia store payouts-модуля provider-кабинета.
 *
 * Управляет списком транзакций, настройками выплат и состоянием загрузки.
 */
export const usePayoutsStore = defineStore('payouts', () => {
  const transactions = ref<PayoutTransaction[]>([])
  const settings = ref<PayoutSettings | null>(null)
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  /** Загружает список транзакций для organization. */
  async function loadTransactions(slug: string): Promise<void> {
    isLoading.value = true
    error.value = null
    try {
      const envelope = await payoutsApi.listTransactions(slug)
      transactions.value = envelope.success && envelope.data ? envelope.data : []
    } catch (err) {
      error.value = extractMessage(err, 'Не удалось загрузить транзакции')
      transactions.value = []
      throw err
    } finally {
      isLoading.value = false
    }
  }

  /** Загружает настройки выплат для organization. */
  async function loadSettings(slug: string): Promise<void> {
    isLoading.value = true
    error.value = null
    try {
      const envelope = await payoutsApi.getSettings(slug)
      settings.value = envelope.success && envelope.data ? envelope.data : null
    } catch (err) {
      error.value = extractMessage(err, 'Не удалось загрузить настройки')
      settings.value = null
      throw err
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Обновляет настройки выплат. При успехе обновляет локальный state.
   */
  async function updateSettings(
    slug: string,
    payload: Partial<PayoutSettings>,
  ): Promise<void> {
    isLoading.value = true
    error.value = null
    try {
      const envelope = await payoutsApi.updateSettings(slug, payload)
      if (envelope.success && envelope.data) {
        settings.value = envelope.data
      }
    } catch (err) {
      error.value = extractMessage(err, 'Не удалось обновить настройки')
      throw err
    } finally {
      isLoading.value = false
    }
  }

  return {
    transactions,
    settings,
    isLoading,
    error,
    loadTransactions,
    loadSettings,
    updateSettings,
  }
})
