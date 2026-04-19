import { apiClient } from './client'
import type { OrgStats } from '@/stores/org.store'
import type { Envelope } from '@/types/auth.types'

/**
 * GET /organizations/{slug}/stats — dashboard-статистика organization.
 *
 * NOTE: backend endpoint пока не реализован. Возвращаем raw envelope —
 * обработка 404 в store через try/catch.
 */
export async function getStats(slug: string): Promise<Envelope<OrgStats>> {
  const resp = await apiClient.get<Envelope<OrgStats>>(
    `/organizations/${encodeURIComponent(slug)}/stats`,
  )
  return resp.data
}
