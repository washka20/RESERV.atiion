import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import { Home, Search, Bell, User } from 'lucide-vue-next'
import BaseBottomNav from '../BaseBottomNav.vue'

const items = [
  { id: 'home', label: 'Home', icon: Home },
  { id: 'search', label: 'Search', icon: Search },
  { id: 'bell', label: 'Alerts', icon: Bell, badge: 3 },
  { id: 'profile', label: 'Profile', icon: User },
]

describe('BaseBottomNav', () => {
  it('renders items with labels', () => {
    const wrapper = mount(BaseBottomNav, {
      props: { items, modelValue: 'home' },
    })
    expect(wrapper.findAll('[data-test-id^="base-bottom-nav-item-"]').length).toBe(4)
    expect(wrapper.text()).toContain('Home')
    expect(wrapper.text()).toContain('Alerts')
  })

  it('shows badge count', () => {
    const wrapper = mount(BaseBottomNav, {
      props: { items, modelValue: 'home' },
    })
    const badge = wrapper.find('[data-test-id="base-bottom-nav-badge-bell"]')
    expect(badge.exists()).toBe(true)
    expect(badge.text()).toBe('3')
  })

  it('marks active item with aria-current', () => {
    const wrapper = mount(BaseBottomNav, {
      props: { items, modelValue: 'search' },
    })
    expect(
      wrapper
        .find('[data-test-id="base-bottom-nav-item-search"]')
        .attributes('aria-current'),
    ).toBe('page')
    expect(
      wrapper
        .find('[data-test-id="base-bottom-nav-item-home"]')
        .attributes('aria-current'),
    ).toBeUndefined()
  })

  it('emits update:modelValue on item click', async () => {
    const wrapper = mount(BaseBottomNav, {
      props: { items, modelValue: 'home' },
    })
    await wrapper
      .find('[data-test-id="base-bottom-nav-item-search"]')
      .trigger('click')
    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual(['search'])
  })

  it('does not emit when clicking already active item', async () => {
    const wrapper = mount(BaseBottomNav, {
      props: { items, modelValue: 'home' },
    })
    await wrapper.find('[data-test-id="base-bottom-nav-item-home"]').trigger('click')
    expect(wrapper.emitted('update:modelValue')).toBeUndefined()
  })
})
