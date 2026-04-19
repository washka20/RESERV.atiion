import { apiClient } from './client'
import type { Envelope } from '@/types/auth.types'
import type { Service, ServiceListItem } from '@/types/catalog.types'

/**
 * Payload создания/обновления услуги (org-scoped).
 */
export interface ServicePayload {
  name: string
  description: string
  price_amount: number
  price_currency: 'RUB' | 'USD' | 'EUR'
  type: 'time_slot' | 'quantity'
  category_id: string
  subcategory_id?: string | null
  is_active?: boolean
}

/**
 * GET /organizations/{slug}/services — список услуг organization.
 *
 * NOTE: на момент написания backend endpoint для org-scoped services пока stub.
 * Customer-side catalog endpoint живёт в catalog.api.ts.
 */
export async function list(slug: string): Promise<Envelope<ServiceListItem[]>> {
  const resp = await apiClient.get<Envelope<ServiceListItem[]>>(
    `/organizations/${encodeURIComponent(slug)}/services`,
  )
  return resp.data
}

/**
 * GET /organizations/{slug}/services/{id} — детальная услуга organization.
 */
export async function get(slug: string, id: string): Promise<Envelope<Service>> {
  const resp = await apiClient.get<Envelope<Service>>(
    `/organizations/${encodeURIComponent(slug)}/services/${encodeURIComponent(id)}`,
  )
  return resp.data
}

/**
 * POST /organizations/{slug}/services — создать услугу.
 */
export async function create(
  slug: string,
  payload: ServicePayload,
): Promise<Envelope<Service>> {
  const resp = await apiClient.post<Envelope<Service>>(
    `/organizations/${encodeURIComponent(slug)}/services`,
    payload,
  )
  return resp.data
}

/**
 * PUT /organizations/{slug}/services/{id} — обновить услугу.
 */
export async function update(
  slug: string,
  id: string,
  payload: Partial<ServicePayload>,
): Promise<Envelope<Service>> {
  const resp = await apiClient.put<Envelope<Service>>(
    `/organizations/${encodeURIComponent(slug)}/services/${encodeURIComponent(id)}`,
    payload,
  )
  return resp.data
}
