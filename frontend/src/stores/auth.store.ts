import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import * as authApi from '@/api/auth.api'
import {
  clearTokens,
  getAccessToken,
  getRefreshToken,
  setTokens,
} from '@/api/client'
import type {
  LoginPayload,
  Membership,
  MembershipPermission,
  MembershipRole,
  RegisterPayload,
  User,
} from '@/types/auth.types'

/**
 * Permissions matrix — зеркало `App\Modules\Identity\Domain\ValueObject\MembershipRole::PERMISSIONS`.
 *
 * Используется только для UX-гейтинга (скрыть кнопку, redirect на /forbidden).
 * Реальный authz — всегда на backend (`MembershipGuardMiddleware`).
 */
const PERMISSIONS: Record<MembershipRole, MembershipPermission[]> = {
  owner: [
    'services.create',
    'services.edit',
    'services.delete',
    'bookings.confirm',
    'bookings.cancel',
    'bookings.view',
    'payouts.view',
    'payouts.manage',
    'analytics.view',
    'team.view',
    'team.manage',
    'settings.view',
    'settings.manage',
    'organization.archive',
  ],
  admin: [
    'services.create',
    'services.edit',
    'services.delete',
    'bookings.confirm',
    'bookings.cancel',
    'bookings.view',
    'payouts.view',
    'analytics.view',
    'team.view',
    'settings.view',
    'settings.manage',
  ],
  staff: [
    'services.edit',
    'bookings.confirm',
    'bookings.cancel',
    'bookings.view',
    'team.view',
    'settings.view',
  ],
  viewer: ['bookings.view'],
}

function extractMessage(err: unknown, fallback: string): string {
  if (err && typeof err === 'object' && 'response' in err) {
    const res = (err as {
      response?: { status?: number; data?: { error?: { message?: string } } }
    }).response
    const envMsg = res?.data?.error?.message
    if (envMsg) return envMsg
    const status = res?.status
    if (status === 401) return 'Неверный email или пароль'
    if (status === 403) return 'Недостаточно прав'
    if (status === 404) return 'Не найдено'
    if (status === 422) return 'Проверьте введённые данные'
    if (status === 429) return 'Слишком много попыток, попробуйте позже'
    if (status && status >= 500) return 'Ошибка сервера — попробуйте позже'
  }
  if (err instanceof Error) {
    if (/^Request failed with status code/.test(err.message)) return fallback
    return err.message
  }
  return fallback
}

/**
 * Pinia store для auth-модуля.
 *
 * Управляет user, парой токенов, memberships и состоянием загрузки.
 * Токены зеркалируются в localStorage через setTokens/clearTokens из api/client.ts —
 * это нужно чтобы axios interceptor подтягивал Authorization header.
 *
 * `hydrate()` вызывается при старте приложения (main.ts) — если в localStorage
 * есть access token, store подгружает user и memberships.
 */
export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const accessToken = ref<string | null>(null)
  const refreshToken = ref<string | null>(null)
  const memberships = ref<Membership[]>([])
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  const isAuthenticated = computed((): boolean => {
    return accessToken.value !== null && user.value !== null
  })

  /**
   * Возвращает membership для указанной organization slug или null.
   */
  function activeMembership(orgSlug: string): Membership | null {
    return memberships.value.find((m) => m.organizationSlug === orgSlug) ?? null
  }

  /**
   * Проверяет, есть ли у текущего user'а permission в указанной organization.
   *
   * Client-side UX gate — backend всё равно проверяет через middleware.
   */
  function canAccessOrg(orgSlug: string, permission: MembershipPermission): boolean {
    const membership = activeMembership(orgSlug)
    if (!membership) return false
    const allowed = PERMISSIONS[membership.role]
    return allowed.includes(permission)
  }

  function applyTokens(access: string, refresh: string): void {
    accessToken.value = access
    refreshToken.value = refresh
    setTokens(access, refresh)
  }

  function resetState(): void {
    user.value = null
    accessToken.value = null
    refreshToken.value = null
    memberships.value = []
    error.value = null
  }

  /**
   * Логин по email/password. При успехе сохраняет токены, затем подгружает
   * профиль (`/auth/me`) и memberships (`/me/memberships`).
   */
  async function login(payload: LoginPayload): Promise<void> {
    isLoading.value = true
    error.value = null
    try {
      const envelope = await authApi.login(payload)
      if (!envelope.success || !envelope.data) {
        throw new Error(envelope.error?.message ?? 'Не удалось войти')
      }
      applyTokens(envelope.data.accessToken, envelope.data.refreshToken)
      await loadMe()
      await loadMemberships()
    } catch (err) {
      const message = extractMessage(err, 'Не удалось войти')
      resetState()
      clearTokens()
      error.value = message
      throw err
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Регистрация нового user'а. Backend возвращает user + tokens сразу — grab all.
   */
  async function register(payload: RegisterPayload): Promise<void> {
    isLoading.value = true
    error.value = null
    try {
      const envelope = await authApi.register(payload)
      if (!envelope.success || !envelope.data) {
        throw new Error(envelope.error?.message ?? 'Не удалось зарегистрироваться')
      }
      applyTokens(envelope.data.accessToken, envelope.data.refreshToken)
      user.value = envelope.data.user
      await loadMemberships()
    } catch (err) {
      const message = extractMessage(err, 'Не удалось зарегистрироваться')
      resetState()
      clearTokens()
      error.value = message
      throw err
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Ручной refresh токена через API. Обычно не нужен — interceptor делает это
   * автоматически на 401. Используется из hydrate() и тестов.
   */
  async function refresh(): Promise<void> {
    const token = refreshToken.value ?? getRefreshToken()
    if (!token) throw new Error('Нет refresh токена')
    const envelope = await authApi.refresh(token)
    if (!envelope.success || !envelope.data) {
      resetState()
      clearTokens()
      throw new Error(envelope.error?.message ?? 'Не удалось обновить сессию')
    }
    applyTokens(envelope.data.accessToken, envelope.data.refreshToken)
  }

  /** Загружает user через /auth/me в state. */
  async function loadMe(): Promise<void> {
    const envelope = await authApi.me()
    if (envelope.success && envelope.data) {
      user.value = envelope.data
    } else {
      user.value = null
    }
  }

  /** Загружает memberships текущего user'а в state. */
  async function loadMemberships(): Promise<void> {
    try {
      const envelope = await authApi.listMemberships()
      memberships.value = envelope.success && envelope.data ? envelope.data : []
    } catch {
      memberships.value = []
    }
  }

  /**
   * Логаут: revoke refresh token на backend + clearTokens локально.
   * Ошибки сетевого вызова игнорируются — локальное состояние всё равно reset'ится.
   */
  async function logout(): Promise<void> {
    const rt = refreshToken.value ?? getRefreshToken()
    try {
      await authApi.logout(rt)
    } catch {
      // игнорируем сетевые ошибки при логауте — user всё равно выходит локально
    }
    resetState()
    clearTokens()
  }

  /**
   * Hydration при старте app: если в localStorage есть access token,
   * подгружает user и memberships. Вызывается из main.ts после pinia.use().
   *
   * При неудачном /auth/me (невалидный token) — чистит всё. Refresh token
   * interceptor может автоматически обновить токен в процессе /auth/me.
   */
  async function hydrate(): Promise<void> {
    const access = getAccessToken()
    const refreshTok = getRefreshToken()
    if (!access) return

    accessToken.value = access
    refreshToken.value = refreshTok

    try {
      await loadMe()
      if (user.value === null) {
        resetState()
        clearTokens()
        return
      }
      await loadMemberships()
    } catch {
      resetState()
      clearTokens()
    }
  }

  return {
    user,
    accessToken,
    refreshToken,
    memberships,
    isLoading,
    error,
    isAuthenticated,
    activeMembership,
    canAccessOrg,
    login,
    register,
    refresh,
    loadMe,
    loadMemberships,
    logout,
    hydrate,
  }
})
