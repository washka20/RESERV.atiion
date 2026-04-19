import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import DesignSystemView from '../DesignSystemView.vue'

describe('DesignSystemView', () => {
  it('renders without errors and exposes nav items', () => {
    const wrapper = mount(DesignSystemView, {
      global: {
        stubs: {
          // Teleport-портированные компоненты лучше stub-ить, чтобы jsdom не ругался
          BaseToast: true,
        },
      },
    })
    expect(wrapper.find('[data-test-id="design-system-view"]').exists()).toBe(true)
  })

  it('renders anchor nav with all 12 sections', () => {
    const wrapper = mount(DesignSystemView)
    const nav = wrapper.find('[data-test-id="design-system-nav"]')
    expect(nav.exists()).toBe(true)
    const links = nav.findAll('a')
    expect(links.length).toBe(12)
  })

  it('renders theme toggle buttons', () => {
    const wrapper = mount(DesignSystemView)
    const toggle = wrapper.find('[data-test-id="design-system-theme-toggle"]')
    expect(toggle.exists()).toBe(true)
    expect(toggle.findAll('button').length).toBeGreaterThanOrEqual(3)
  })
})
