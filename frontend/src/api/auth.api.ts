import { apiClient } from './client'
import type {
  AuthLoginResponse,
  AuthRegisterResponse,
  Envelope,
  LoginPayload,
  Membership,
  RawMembership,
  RawUser,
  RegisterPayload,
  User,
} from '@/types/auth.types'

/**
 * Преобразует "сырой" user из envelope в доменный User (camelCase).
 */
export function mapUser(raw: RawUser): User {
  return {
    id: raw.id,
    email: raw.email,
    firstName: raw.first_name,
    lastName: raw.last_name,
    middleName: raw.middle_name,
    roles: raw.roles,
    emailVerifiedAt: raw.email_verified_at,
    createdAt: raw.created_at,
  }
}

/**
 * Преобразует сырой элемент списка /me/memberships в доменный Membership.
 */
export function mapMembership(raw: RawMembership): Membership {
  return {
    membershipId: raw.membership_id,
    organizationId: raw.organization_id,
    organizationSlug: raw.organization_slug,
    role: raw.role,
  }
}

interface RawAuthRegisterResponse {
  user: RawUser | null
  access_token: string
  refresh_token: string
  expires_in: number
  token_type: string
}

interface RawAuthLoginResponse {
  access_token: string
  refresh_token: string
  expires_in: number
  token_type: string
}

/**
 * POST /auth/login — логин по email/password.
 *
 * Backend возвращает только пару токенов (без user). Для получения профиля
 * используй {@link me} после успешного login.
 */
export async function login(payload: LoginPayload): Promise<Envelope<AuthLoginResponse>> {
  const resp = await apiClient.post<Envelope<RawAuthLoginResponse>>('/auth/login', payload)
  const raw = resp.data
  if (!raw.success || !raw.data) {
    return { success: raw.success, data: null, error: raw.error, meta: raw.meta }
  }
  return {
    success: true,
    data: {
      accessToken: raw.data.access_token,
      refreshToken: raw.data.refresh_token,
      expiresIn: raw.data.expires_in,
      tokenType: raw.data.token_type,
    },
    error: null,
    meta: raw.meta,
  }
}

/**
 * POST /auth/register — регистрация нового пользователя.
 *
 * Возвращает user + пару токенов (201).
 */
export async function register(
  payload: RegisterPayload,
): Promise<Envelope<AuthRegisterResponse>> {
  const resp = await apiClient.post<Envelope<RawAuthRegisterResponse>>('/auth/register', payload)
  const raw = resp.data
  if (!raw.success || !raw.data) {
    return { success: raw.success, data: null, error: raw.error, meta: raw.meta }
  }
  return {
    success: true,
    data: {
      user: raw.data.user ? mapUser(raw.data.user) : null,
      accessToken: raw.data.access_token,
      refreshToken: raw.data.refresh_token,
      expiresIn: raw.data.expires_in,
      tokenType: raw.data.token_type,
    },
    error: null,
    meta: raw.meta,
  }
}

/**
 * POST /auth/refresh — обновление access token.
 *
 * Вызывается в основном через axios interceptor в client.ts; здесь — для ручного
 * refresh из store. Возвращает новые access + refresh токены.
 */
export async function refresh(refreshToken: string): Promise<Envelope<AuthLoginResponse>> {
  const resp = await apiClient.post<Envelope<RawAuthLoginResponse>>('/auth/refresh', {
    refresh_token: refreshToken,
  })
  const raw = resp.data
  if (!raw.success || !raw.data) {
    return { success: raw.success, data: null, error: raw.error, meta: raw.meta }
  }
  return {
    success: true,
    data: {
      accessToken: raw.data.access_token,
      refreshToken: raw.data.refresh_token,
      expiresIn: raw.data.expires_in,
      tokenType: raw.data.token_type,
    },
    error: null,
    meta: raw.meta,
  }
}

/**
 * GET /auth/me — профиль текущего пользователя.
 *
 * Требует Authorization header (проставляет interceptor).
 */
export async function me(): Promise<Envelope<User>> {
  const resp = await apiClient.get<Envelope<RawUser>>('/auth/me')
  const raw = resp.data
  if (!raw.success || !raw.data) {
    return { success: raw.success, data: null, error: raw.error, meta: raw.meta }
  }
  return { success: true, data: mapUser(raw.data), error: null, meta: raw.meta }
}

/**
 * GET /me/memberships — список organizations, в которых user является member.
 */
export async function listMemberships(): Promise<Envelope<Membership[]>> {
  const resp = await apiClient.get<Envelope<RawMembership[]>>('/me/memberships')
  const raw = resp.data
  if (!raw.success || !raw.data) {
    return { success: raw.success, data: null, error: raw.error, meta: raw.meta }
  }
  return {
    success: true,
    data: raw.data.map(mapMembership),
    error: null,
    meta: raw.meta,
  }
}

/**
 * POST /auth/logout — ревокация refresh token.
 *
 * Backend отвечает 204 No Content. Ошибки логаута игнорируются в store
 * (всё равно делаем clearTokens локально).
 */
export async function logout(refreshToken: string | null): Promise<void> {
  const body = refreshToken ? { refresh_token: refreshToken } : {}
  await apiClient.post('/auth/logout', body)
}
