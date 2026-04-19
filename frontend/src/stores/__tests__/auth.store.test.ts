import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { useAuthStore } from '@/stores/auth.store'
import type { Envelope, User, Membership, AuthLoginResponse, AuthRegisterResponse } from '@/types/auth.types'

vi.mock('@/api/auth.api', () => ({
  login: vi.fn(),
  register: vi.fn(),
  refresh: vi.fn(),
  me: vi.fn(),
  listMemberships: vi.fn(),
  logout: vi.fn(),
}))

import * as authApi from '@/api/auth.api'

const mockedLogin = vi.mocked(authApi.login)
const mockedRegister = vi.mocked(authApi.register)
const _mockedRefresh = vi.mocked(authApi.refresh)
const mockedMe = vi.mocked(authApi.me)
const mockedListMemberships = vi.mocked(authApi.listMemberships)
const mockedLogout = vi.mocked(authApi.logout)

const sampleUser: User = {
  id: 'user-1',
  email: 'owner@example.com',
  firstName: 'Иван',
  lastName: 'Иванов',
  middleName: null,
  roles: ['user'],
  emailVerifiedAt: null,
  createdAt: '2026-04-19T10:00:00+00:00',
}

const sampleMemberships: Membership[] = [
  {
    membershipId: 'm-1',
    organizationId: 'org-1',
    organizationSlug: 'acme',
    role: 'owner',
  },
  {
    membershipId: 'm-2',
    organizationId: 'org-2',
    organizationSlug: 'beta',
    role: 'staff',
  },
]

function loginEnvelope(): Envelope<AuthLoginResponse> {
  return {
    success: true,
    data: {
      accessToken: 'access-123',
      refreshToken: 'refresh-123',
      expiresIn: 3600,
      tokenType: 'Bearer',
    },
    error: null,
    meta: null,
  }
}

function registerEnvelope(): Envelope<AuthRegisterResponse> {
  return {
    success: true,
    data: {
      user: sampleUser,
      accessToken: 'access-reg',
      refreshToken: 'refresh-reg',
      expiresIn: 3600,
      tokenType: 'Bearer',
    },
    error: null,
    meta: null,
  }
}

function meEnvelope(): Envelope<User> {
  return { success: true, data: sampleUser, error: null, meta: null }
}

function membershipsEnvelope(): Envelope<Membership[]> {
  return { success: true, data: sampleMemberships, error: null, meta: null }
}

describe('useAuthStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
    vi.clearAllMocks()
  })

  describe('login', () => {
    it('saves tokens in localStorage and loads user + memberships', async () => {
      mockedLogin.mockResolvedValueOnce(loginEnvelope())
      mockedMe.mockResolvedValueOnce(meEnvelope())
      mockedListMemberships.mockResolvedValueOnce(membershipsEnvelope())

      const store = useAuthStore()
      await store.login({ email: 'owner@example.com', password: 'secret123' })

      expect(localStorage.getItem('auth:token')).toBe('access-123')
      expect(localStorage.getItem('auth:refresh')).toBe('refresh-123')
      expect(store.user).toEqual(sampleUser)
      expect(store.memberships).toEqual(sampleMemberships)
      expect(store.isAuthenticated).toBe(true)
      expect(store.error).toBeNull()
    })

    it('resets state and throws on failed envelope', async () => {
      mockedLogin.mockResolvedValueOnce({
        success: false,
        data: null,
        error: { code: 'INVALID_CREDENTIALS', message: 'Invalid credentials' },
        meta: null,
      })

      const store = useAuthStore()
      await expect(
        store.login({ email: 'x@example.com', password: 'wrong' }),
      ).rejects.toThrow(/Invalid credentials/i)
      expect(store.isAuthenticated).toBe(false)
      expect(localStorage.getItem('auth:token')).toBeNull()
      expect(store.error).toBe('Invalid credentials')
    })
  })

  describe('register', () => {
    it('saves tokens and user from register response', async () => {
      mockedRegister.mockResolvedValueOnce(registerEnvelope())
      mockedListMemberships.mockResolvedValueOnce({
        success: true,
        data: [],
        error: null,
        meta: null,
      })

      const store = useAuthStore()
      await store.register({
        email: 'new@example.com',
        password: 'secret123',
        first_name: 'Пётр',
        last_name: 'Петров',
      })

      expect(localStorage.getItem('auth:token')).toBe('access-reg')
      expect(store.user).toEqual(sampleUser)
      expect(store.isAuthenticated).toBe(true)
    })
  })

  describe('logout', () => {
    it('clears tokens and resets state', async () => {
      localStorage.setItem('auth:token', 'access-old')
      localStorage.setItem('auth:refresh', 'refresh-old')
      mockedLogout.mockResolvedValueOnce()

      const store = useAuthStore()
      store.accessToken = 'access-old'
      store.refreshToken = 'refresh-old'
      store.user = sampleUser
      store.memberships = sampleMemberships

      await store.logout()

      expect(localStorage.getItem('auth:token')).toBeNull()
      expect(localStorage.getItem('auth:refresh')).toBeNull()
      expect(store.user).toBeNull()
      expect(store.memberships).toEqual([])
      expect(store.isAuthenticated).toBe(false)
    })

    it('resets state even if logout API call fails', async () => {
      localStorage.setItem('auth:token', 'access-old')
      localStorage.setItem('auth:refresh', 'refresh-old')
      mockedLogout.mockRejectedValueOnce(new Error('network down'))

      const store = useAuthStore()
      store.accessToken = 'access-old'
      store.refreshToken = 'refresh-old'
      store.user = sampleUser

      await store.logout()

      expect(localStorage.getItem('auth:token')).toBeNull()
      expect(store.user).toBeNull()
    })
  })

  describe('hydrate', () => {
    it('loads user if access token present in localStorage', async () => {
      localStorage.setItem('auth:token', 'access-stored')
      localStorage.setItem('auth:refresh', 'refresh-stored')
      mockedMe.mockResolvedValueOnce(meEnvelope())
      mockedListMemberships.mockResolvedValueOnce(membershipsEnvelope())

      const store = useAuthStore()
      await store.hydrate()

      expect(store.user).toEqual(sampleUser)
      expect(store.memberships).toEqual(sampleMemberships)
      expect(store.isAuthenticated).toBe(true)
    })

    it('does nothing if no token in localStorage', async () => {
      const store = useAuthStore()
      await store.hydrate()

      expect(mockedMe).not.toHaveBeenCalled()
      expect(store.user).toBeNull()
      expect(store.isAuthenticated).toBe(false)
    })

    it('clears tokens if /auth/me returns no data', async () => {
      localStorage.setItem('auth:token', 'bad-token')
      localStorage.setItem('auth:refresh', 'bad-refresh')
      mockedMe.mockResolvedValueOnce({
        success: false,
        data: null,
        error: { code: 'UNAUTHORIZED', message: 'Unauthorized' },
        meta: null,
      })

      const store = useAuthStore()
      await store.hydrate()

      expect(localStorage.getItem('auth:token')).toBeNull()
      expect(store.user).toBeNull()
    })
  })

  describe('canAccessOrg', () => {
    it('returns true for owner with payouts.manage', async () => {
      mockedLogin.mockResolvedValueOnce(loginEnvelope())
      mockedMe.mockResolvedValueOnce(meEnvelope())
      mockedListMemberships.mockResolvedValueOnce(membershipsEnvelope())

      const store = useAuthStore()
      await store.login({ email: 'owner@example.com', password: 'secret' })

      expect(store.canAccessOrg('acme', 'payouts.manage')).toBe(true)
    })

    it('returns false for staff with payouts.manage', async () => {
      mockedLogin.mockResolvedValueOnce(loginEnvelope())
      mockedMe.mockResolvedValueOnce(meEnvelope())
      mockedListMemberships.mockResolvedValueOnce(membershipsEnvelope())

      const store = useAuthStore()
      await store.login({ email: 'owner@example.com', password: 'secret' })

      expect(store.canAccessOrg('beta', 'payouts.manage')).toBe(false)
      expect(store.canAccessOrg('beta', 'bookings.view')).toBe(true)
    })

    it('returns false for unknown organization', async () => {
      const store = useAuthStore()
      expect(store.canAccessOrg('unknown', 'bookings.view')).toBe(false)
    })
  })
})
