import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createMemoryHistory, type Router } from 'vue-router'
import AvatarMenu from '../AvatarMenu.vue'
import { useAuthStore } from '@/stores/auth.store'

vi.mock('@/api/auth.api', () => ({
  login: vi.fn(),
  register: vi.fn(),
  refresh: vi.fn(),
  me: vi.fn(),
  listMemberships: vi.fn(),
  logout: vi.fn().mockResolvedValue(undefined),
}))

function makeRouter(): Router {
  return createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/catalog', name: 'catalog', component: { template: '<div />' } },
      { path: '/dashboard', name: 'dashboard', component: { template: '<div />' } },
    ],
  })
}

describe('AvatarMenu', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  afterEach(() => {
    document.body.innerHTML = ''
  })

  it('renders closed by default', () => {
    const router = makeRouter()
    const wrapper = mount(AvatarMenu, {
      props: { fullName: 'Иван Иванов', initials: 'ИИ' },
      global: { plugins: [router] },
    })
    expect(
      wrapper.find('[data-test-id="app-header-avatar-menu-dropdown"]').exists(),
    ).toBe(false)
  })

  it('opens dropdown on avatar click', async () => {
    const router = makeRouter()
    const wrapper = mount(AvatarMenu, {
      props: { fullName: 'Иван Иванов', initials: 'ИИ' },
      global: { plugins: [router] },
      attachTo: document.body,
    })
    await wrapper.find('[data-test-id="app-header-avatar"]').trigger('click')
    expect(
      wrapper.find('[data-test-id="app-header-avatar-menu-dropdown"]').exists(),
    ).toBe(true)
    expect(wrapper.find('[data-test-id="avatar-menu-profile"]').exists()).toBe(true)
    expect(wrapper.find('[data-test-id="avatar-menu-settings"]').exists()).toBe(true)
    expect(wrapper.find('[data-test-id="avatar-menu-logout"]').exists()).toBe(true)
  })

  it('logout triggers authStore.logout + redirect to /catalog', async () => {
    const router = makeRouter()
    await router.push('/dashboard')
    await router.isReady()

    const wrapper = mount(AvatarMenu, {
      props: { fullName: 'Иван Иванов', initials: 'ИИ' },
      global: { plugins: [router] },
      attachTo: document.body,
    })

    const auth = useAuthStore()
    const logoutSpy = vi.spyOn(auth, 'logout')

    await wrapper.find('[data-test-id="app-header-avatar"]').trigger('click')
    await wrapper.find('[data-test-id="avatar-menu-logout"]').trigger('click')
    await new Promise((r) => setTimeout(r, 0))

    expect(logoutSpy).toHaveBeenCalledOnce()
    expect(router.currentRoute.value.name).toBe('catalog')
  })
})
