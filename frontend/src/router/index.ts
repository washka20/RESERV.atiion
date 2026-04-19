import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth.store'
import type { MembershipPermission } from '@/types/auth.types'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      redirect: { name: 'catalog' },
    },
    {
      path: '/catalog',
      name: 'catalog',
      component: () => import('@/modules/catalog/views/CatalogView.vue'),
    },
    {
      path: '/catalog/:id',
      name: 'catalog-service',
      component: () => import('@/modules/catalog/views/ServiceDetailView.vue'),
      props: true,
    },
    {
      path: '/book/:serviceId',
      name: 'booking-new',
      component: () => import('@/modules/booking/views/BookingView.vue'),
      props: true,
      meta: { requiresAuth: true },
    },
    {
      path: '/bookings/:id',
      name: 'booking-confirm',
      component: () => import('@/modules/booking/views/BookingConfirmView.vue'),
      props: true,
      meta: { requiresAuth: true },
    },
    {
      path: '/dashboard',
      name: 'dashboard',
      component: () => import('@/modules/dashboard/views/DashboardView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/login',
      name: 'login',
      component: () => import('@/modules/auth/views/LoginView.vue'),
    },
    {
      path: '/register',
      name: 'auth-register',
      component: () => import('@/modules/auth/views/RegisterRoleView.vue'),
    },
    {
      path: '/register/customer',
      name: 'auth-register-customer',
      component: () => import('@/modules/auth/views/RegisterCustomerView.vue'),
    },
    {
      path: '/register/provider',
      name: 'auth-register-provider',
      component: () => import('@/modules/auth/views/RegisterProviderView.vue'),
    },
    {
      path: '/verify-email/:token',
      name: 'auth-verify-email',
      component: () => import('@/modules/auth/views/VerifyEmailView.vue'),
      props: true,
    },
    {
      path: '/verify-phone',
      name: 'auth-verify-phone',
      component: () => import('@/modules/auth/views/VerifyPhoneView.vue'),
    },
    {
      path: '/forgot-password',
      name: 'auth-forgot-password',
      component: () => import('@/modules/auth/views/ForgotPasswordView.vue'),
    },
    {
      path: '/reset-password/:token',
      name: 'auth-reset-password',
      component: () => import('@/modules/auth/views/ResetPasswordView.vue'),
      props: true,
    },
    {
      path: '/forbidden',
      name: 'forbidden',
      component: () => import('@/modules/auth/views/ForbiddenView.vue'),
    },
    {
      path: '/design-system',
      name: 'design-system',
      component: () => import('@/modules/design-system/DesignSystemView.vue'),
    },
    {
      path: '/o/:slug',
      component: () => import('@/modules/provider/components/OrgLayout.vue'),
      meta: { requiresAuth: true },
      children: [
        {
          path: '',
          name: 'org-dashboard',
          component: () => import('@/modules/provider/views/OrgDashboardView.vue'),
          meta: { orgPermission: 'analytics.view' },
        },
      ],
    },
    {
      path: '/:pathMatch(.*)*',
      name: 'not-found',
      component: () => import('@/shared/views/NotFoundView.vue'),
    },
  ],
})

/**
 * Router guards.
 *
 * 1. `meta.requiresAuth` — redirect на /login с `?redirect=` saved route.
 * 2. Org routes (`params.slug` + `meta.orgPermission`) — проверка membership
 *    permission клиентски. Backend всё равно проверяет через middleware,
 *    client guard нужен только для UX (избегаем flash незагруженной страницы).
 */
router.beforeEach((to) => {
  const auth = useAuthStore()

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  const orgSlug = to.params.slug
  const requiredPermission = to.meta.orgPermission as MembershipPermission | undefined
  if (typeof orgSlug === 'string' && requiredPermission) {
    if (!auth.canAccessOrg(orgSlug, requiredPermission)) {
      return { name: 'forbidden' }
    }
  }

  return true
})

export default router
