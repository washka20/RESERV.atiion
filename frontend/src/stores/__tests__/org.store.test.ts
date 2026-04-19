import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { useOrgStore, type OrgStats } from '@/stores/org.store'
import type { Envelope } from '@/types/auth.types'

vi.mock('@/api/org.api', () => ({
  getStats: vi.fn(),
}))

import * as orgApi from '@/api/org.api'

const mockedGetStats = vi.mocked(orgApi.getStats)

const sampleStats: OrgStats = {
  totalBookings: 42,
  pendingBookings: 5,
  confirmedBookings: 30,
  revenue: 125000,
}

function statsEnvelope(): Envelope<OrgStats> {
  return { success: true, data: sampleStats, error: null, meta: null }
}

describe('useOrgStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('setActive updates activeOrgSlug', () => {
    const store = useOrgStore()
    store.setActive('acme')
    expect(store.activeOrgSlug).toBe('acme')
  })

  it('clearActive resets state', () => {
    const store = useOrgStore()
    store.setActive('acme')
    store.orgStats = sampleStats
    store.clearActive()
    expect(store.activeOrgSlug).toBeNull()
    expect(store.orgStats).toBeNull()
  })

  it('loadStats saves stats from envelope', async () => {
    mockedGetStats.mockResolvedValueOnce(statsEnvelope())
    const store = useOrgStore()
    await store.loadStats('acme')
    expect(store.orgStats).toEqual(sampleStats)
    expect(store.error).toBeNull()
  })

  it('loadStats sets null on failed envelope', async () => {
    mockedGetStats.mockResolvedValueOnce({
      success: false,
      data: null,
      error: { code: 'NOT_FOUND', message: 'Organization not found' },
      meta: null,
    })
    const store = useOrgStore()
    await store.loadStats('missing')
    expect(store.orgStats).toBeNull()
  })
})
