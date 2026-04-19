import { beforeEach, describe, expect, it } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { useAuthStore } from '@/stores/auth.store'
import { useOrgPermission } from '../useOrgPermission'
import type { Membership } from '@/types/auth.types'

const ownerMembership: Membership = {
  membershipId: 'm-1',
  organizationId: 'org-1',
  organizationSlug: 'acme',
  role: 'owner',
}

const staffMembership: Membership = {
  membershipId: 'm-2',
  organizationId: 'org-2',
  organizationSlug: 'beta',
  role: 'staff',
}

describe('useOrgPermission', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('returns true when owner has payouts.manage', () => {
    const auth = useAuthStore()
    auth.memberships = [ownerMembership]
    const allowed = useOrgPermission('acme', 'payouts.manage')
    expect(allowed.value).toBe(true)
  })

  it('returns false when staff tries payouts.manage', () => {
    const auth = useAuthStore()
    auth.memberships = [staffMembership]
    const allowed = useOrgPermission('beta', 'payouts.manage')
    expect(allowed.value).toBe(false)
  })

  it('returns true when staff has bookings.view', () => {
    const auth = useAuthStore()
    auth.memberships = [staffMembership]
    const allowed = useOrgPermission('beta', 'bookings.view')
    expect(allowed.value).toBe(true)
  })

  it('returns false for unknown org slug', () => {
    const auth = useAuthStore()
    auth.memberships = [ownerMembership]
    const allowed = useOrgPermission('unknown', 'bookings.view')
    expect(allowed.value).toBe(false)
  })
})
