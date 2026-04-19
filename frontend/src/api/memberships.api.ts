import { apiClient } from './client'
import type { Envelope, MembershipRole } from '@/types/auth.types'

/**
 * Membership организации с данными user (для provider team-view).
 */
export interface OrgMember {
  id: string
  userId: string
  email: string
  firstName: string
  lastName: string
  role: MembershipRole
  acceptedAt: string | null
  createdAt: string
}

/**
 * Payload приглашения в organization.
 */
export interface InviteMembershipPayload {
  email: string
  role: MembershipRole
}

/**
 * GET /organizations/{slug}/memberships — список members organization.
 *
 * NOTE: backend endpoint может быть не реализован в MVP. Обрабатываем 404
 * на стороне caller.
 */
export async function list(slug: string): Promise<Envelope<OrgMember[]>> {
  const resp = await apiClient.get<Envelope<OrgMember[]>>(
    `/organizations/${encodeURIComponent(slug)}/memberships`,
  )
  return resp.data
}

/**
 * POST /organizations/{slug}/memberships — пригласить user'а в organization.
 *
 * NOTE: backend endpoint может быть не реализован в MVP.
 */
export async function invite(
  slug: string,
  payload: InviteMembershipPayload,
): Promise<Envelope<OrgMember>> {
  const resp = await apiClient.post<Envelope<OrgMember>>(
    `/organizations/${encodeURIComponent(slug)}/memberships`,
    payload,
  )
  return resp.data
}
