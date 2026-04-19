import { createRouter, createWebHistory } from 'vue-router'

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
      path: '/design-system',
      name: 'design-system',
      component: () => import('@/modules/design-system/DesignSystemView.vue'),
    },
    {
      path: '/:pathMatch(.*)*',
      name: 'not-found',
      component: () => import('@/shared/views/NotFoundView.vue'),
    },
  ],
})

/**
 * Guard для роутов с meta.requiresAuth: пока /login нет (до Plan 14) —
 * редиректим на каталог. После Plan 14 заменить на redirect /login с saved route.
 */
router.beforeEach((to) => {
  if (to.meta.requiresAuth) {
    const token = localStorage.getItem('auth:token')
    if (!token) {
      return { name: 'catalog', query: { 'auth-required': '1' } }
    }
  }
  return true
})

export default router
