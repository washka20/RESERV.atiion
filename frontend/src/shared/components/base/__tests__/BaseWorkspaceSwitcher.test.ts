import { describe, it, expect, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import BaseWorkspaceSwitcher from '../BaseWorkspaceSwitcher.vue'

const workspaces = [
  { id: 'p1', name: 'Personal', type: 'personal' as const },
  { id: 'o1', name: 'Acme Corp', type: 'organization' as const },
  { id: 'o2', name: 'Beta LLC', type: 'organization' as const },
]

afterEach(() => {
  document.body.innerHTML = ''
})

describe('BaseWorkspaceSwitcher', () => {
  it('renders active workspace name', () => {
    const wrapper = mount(BaseWorkspaceSwitcher, {
      props: { workspaces, modelValue: 'p1' },
    })
    const trigger = wrapper.find('[data-test-id="base-workspace-switcher-trigger"]')
    expect(trigger.text()).toContain('Personal')
  })

  it('menu is hidden initially', () => {
    const wrapper = mount(BaseWorkspaceSwitcher, {
      props: { workspaces, modelValue: 'p1' },
    })
    expect(wrapper.find('[data-test-id="base-workspace-switcher-menu"]').exists()).toBe(
      false,
    )
  })

  it('opens menu on trigger click', async () => {
    const wrapper = mount(BaseWorkspaceSwitcher, {
      props: { workspaces, modelValue: 'p1' },
      attachTo: document.body,
    })
    await wrapper
      .find('[data-test-id="base-workspace-switcher-trigger"]')
      .trigger('click')
    expect(wrapper.find('[data-test-id="base-workspace-switcher-menu"]').exists()).toBe(
      true,
    )
    wrapper.unmount()
  })

  it('emits update:modelValue on option click', async () => {
    const wrapper = mount(BaseWorkspaceSwitcher, {
      props: { workspaces, modelValue: 'p1' },
      attachTo: document.body,
    })
    await wrapper
      .find('[data-test-id="base-workspace-switcher-trigger"]')
      .trigger('click')
    await wrapper
      .find('[data-test-id="base-workspace-switcher-option-o1"]')
      .trigger('click')
    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual(['o1'])
    expect(wrapper.find('[data-test-id="base-workspace-switcher-menu"]').exists()).toBe(
      false,
    )
    wrapper.unmount()
  })
})
