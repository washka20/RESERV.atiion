import { computed, type ComputedRef } from 'vue'
import { useAuthStore } from '@/stores/auth.store'
import type { MembershipPermission } from '@/types/auth.types'

/**
 * Проверяет что текущий user имеет указанный permission в given organization.
 *
 * Использует permissions matrix из auth.store (`canAccessOrg`).
 * Возвращает ComputedRef — reactive на изменения memberships.
 *
 * Client-side UX gate — backend всё равно проверяет через middleware.
 */
export function useOrgPermission(
  orgSlug: string,
  permission: MembershipPermission,
): ComputedRef<boolean> {
  const auth = useAuthStore()
  return computed(() => auth.canAccessOrg(orgSlug, permission))
}
