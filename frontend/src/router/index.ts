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
      path: '/booking/new',
      name: 'booking-create',
      component: () => import('@/modules/catalog/views/BookingStubView.vue'),
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
  ],
})

export default router
