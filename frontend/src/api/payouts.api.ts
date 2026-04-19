import { apiClient } from './client'
import type { PayoutSettings, PayoutTransaction } from '@/stores/payouts.store'
import type { Envelope } from '@/types/auth.types'

/**
 * Query parameters для /payouts endpoint.
 */
export interface ListTransactionsParams {
  page?: number
  perPage?: number
  status?: 'pending' | 'completed' | 'failed'
}

/**
 * GET /organizations/{slug}/payouts — список транзакций выплат.
 *
 * NOTE: backend endpoint пока stub. Envelope возвращается as-is.
 */
export async function listTransactions(
  slug: string,
  params: ListTransactionsParams = {},
): Promise<Envelope<PayoutTransaction[]>> {
  const resp = await apiClient.get<Envelope<PayoutTransaction[]>>(
    `/organizations/${encodeURIComponent(slug)}/payouts`,
    { params },
  )
  return resp.data
}

/**
 * GET /organizations/{slug}/payout-settings — настройки выплат.
 */
export async function getSettings(slug: string): Promise<Envelope<PayoutSettings>> {
  const resp = await apiClient.get<Envelope<PayoutSettings>>(
    `/organizations/${encodeURIComponent(slug)}/payout-settings`,
  )
  return resp.data
}

/**
 * PUT /organizations/{slug}/payout-settings — обновить настройки выплат.
 */
export async function updateSettings(
  slug: string,
  payload: Partial<PayoutSettings>,
): Promise<Envelope<PayoutSettings>> {
  const resp = await apiClient.put<Envelope<PayoutSettings>>(
    `/organizations/${encodeURIComponent(slug)}/payout-settings`,
    payload,
  )
  return resp.data
}
