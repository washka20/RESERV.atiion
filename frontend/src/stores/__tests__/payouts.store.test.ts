import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import {
  usePayoutsStore,
  type PayoutSettings,
  type PayoutTransaction,
} from '@/stores/payouts.store'
import type { Envelope } from '@/types/auth.types'

vi.mock('@/api/payouts.api', () => ({
  listTransactions: vi.fn(),
  getSettings: vi.fn(),
  updateSettings: vi.fn(),
}))

import * as payoutsApi from '@/api/payouts.api'

const mockedList = vi.mocked(payoutsApi.listTransactions)
const mockedGetSettings = vi.mocked(payoutsApi.getSettings)
const mockedUpdateSettings = vi.mocked(payoutsApi.updateSettings)

const sampleTransactions: PayoutTransaction[] = [
  {
    id: 'tx-1',
    amount: 5000,
    currency: 'RUB',
    status: 'completed',
    createdAt: '2026-04-01T12:00:00+00:00',
    description: 'Payout #1',
  },
]

const sampleSettings: PayoutSettings = {
  bankAccount: '40817810...',
  iban: null,
  accountHolder: 'Иван Иванов',
  autoPayoutEnabled: true,
}

function txEnvelope(): Envelope<PayoutTransaction[]> {
  return { success: true, data: sampleTransactions, error: null, meta: null }
}

function settingsEnvelope(): Envelope<PayoutSettings> {
  return { success: true, data: sampleSettings, error: null, meta: null }
}

describe('usePayoutsStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('loadTransactions stores list', async () => {
    mockedList.mockResolvedValueOnce(txEnvelope())
    const store = usePayoutsStore()
    await store.loadTransactions('acme')
    expect(store.transactions).toEqual(sampleTransactions)
  })

  it('loadTransactions resets list on failed envelope', async () => {
    mockedList.mockResolvedValueOnce({
      success: false,
      data: null,
      error: { code: 'FORBIDDEN', message: 'Нет доступа' },
      meta: null,
    })
    const store = usePayoutsStore()
    await store.loadTransactions('acme')
    expect(store.transactions).toEqual([])
  })

  it('loadSettings stores settings', async () => {
    mockedGetSettings.mockResolvedValueOnce(settingsEnvelope())
    const store = usePayoutsStore()
    await store.loadSettings('acme')
    expect(store.settings).toEqual(sampleSettings)
  })

  it('updateSettings updates local state from envelope', async () => {
    const updated: PayoutSettings = { ...sampleSettings, autoPayoutEnabled: false }
    mockedUpdateSettings.mockResolvedValueOnce({
      success: true,
      data: updated,
      error: null,
      meta: null,
    })
    const store = usePayoutsStore()
    await store.updateSettings('acme', { autoPayoutEnabled: false })
    expect(store.settings).toEqual(updated)
  })

  it('updateSettings extracts error message', async () => {
    mockedUpdateSettings.mockRejectedValueOnce({
      response: {
        status: 422,
        data: { error: { message: 'IBAN invalid' } },
      },
    })
    const store = usePayoutsStore()
    await expect(store.updateSettings('acme', {})).rejects.toBeDefined()
    expect(store.error).toBe('IBAN invalid')
  })
})
