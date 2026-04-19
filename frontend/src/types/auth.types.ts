/**
 * TypeScript типы для Identity/Auth модуля.
 *
 * Структура соответствует backend-ответам:
 * - `User` — результат `UserResource::fromDTO()` (snake_case → camelCase mapping в api-клиенте);
 * - `Membership` — результат `MembershipWithOrgResource` из /me/memberships;
 * - `AuthLoginResponse` — только токены (login/refresh), user отдельно через /auth/me;
 * - `AuthRegisterResponse` — user + пара токенов (register возвращает 201 с user'ом).
 */

/**
 * Organization-level RBAC роль membership. Совпадает с
 * `App\Modules\Identity\Domain\ValueObject\MembershipRole` (backend).
 */
export type MembershipRole = 'owner' | 'admin' | 'staff' | 'viewer'

/**
 * Permission keys поддерживаемые backend MembershipRole::PERMISSIONS.
 * Keep in sync с `MembershipRole.php`.
 */
export type MembershipPermission =
  | 'services.create'
  | 'services.edit'
  | 'services.delete'
  | 'bookings.confirm'
  | 'bookings.cancel'
  | 'bookings.view'
  | 'payouts.view'
  | 'payouts.manage'
  | 'analytics.view'
  | 'team.view'
  | 'team.manage'
  | 'settings.view'
  | 'settings.manage'
  | 'organization.archive'

/**
 * Membership текущего user'а в некоторой organization.
 *
 * Поля соответствуют `MembershipWithOrgResource::toArray()` — id, slug, role.
 * Human-readable имя организации backend здесь не возвращает; подтягивай
 * через `GET /organizations/{slug}` при необходимости.
 */
export interface Membership {
  membershipId: string
  organizationId: string
  organizationSlug: string
  role: MembershipRole
}

/** Raw форма Membership (snake_case из envelope). */
export interface RawMembership {
  membership_id: string
  organization_id: string
  organization_slug: string
  role: MembershipRole
}

/**
 * Текущий user. Соответствует `UserResource::fromDTO(UserDTO)`.
 *
 * Memberships загружаются отдельно через `/me/memberships` — в User'е не лежат.
 */
export interface User {
  id: string
  email: string
  firstName: string
  lastName: string
  middleName: string | null
  /** Spatie platform roles: admin, manager, user, etc. */
  roles: string[]
  emailVerifiedAt: string | null
  createdAt: string
}

/** Raw форма User (snake_case из envelope). */
export interface RawUser {
  id: string
  email: string
  first_name: string
  last_name: string
  middle_name: string | null
  roles: string[]
  email_verified_at: string | null
  created_at: string
}

/**
 * Ответ `/auth/login` и `/auth/refresh` — только пара токенов.
 * Для получения user'а сделай отдельный вызов `/auth/me`.
 */
export interface AuthLoginResponse {
  accessToken: string
  refreshToken: string
  expiresIn: number
  tokenType: string
}

/**
 * Ответ `/auth/register` — user + пара токенов.
 */
export interface AuthRegisterResponse extends AuthLoginResponse {
  user: User | null
}

/** Payload для `/auth/login`. */
export interface LoginPayload {
  email: string
  password: string
}

/**
 * Payload для `/auth/register`. Backend ожидает snake_case:
 * email, password, first_name, last_name, middle_name (optional).
 * Phone в текущем RegisterRequest отсутствует (см. Identity/Interface/Api/Request).
 */
export interface RegisterPayload {
  email: string
  password: string
  first_name: string
  last_name: string
  middle_name?: string | null
}

/** Стандартная оболочка ответа API. */
export interface Envelope<T> {
  success: boolean
  data: T | null
  error: { code: string; message: string; details?: unknown } | null
  meta?: Record<string, unknown> | null
}
