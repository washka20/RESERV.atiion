import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import BaseTabs from '../BaseTabs.vue'

const tabs = [
  { id: 'a', label: 'A' },
  { id: 'b', label: 'B' },
  { id: 'c', label: 'C' },
]

describe('BaseTabs', () => {
  it('renders tabs with role=tab and aria-selected', () => {
    const wrapper = mount(BaseTabs, { props: { tabs, modelValue: 'a' } })
    const first = wrapper.find('[data-test-id="base-tab-a"]')
    expect(first.attributes('role')).toBe('tab')
    expect(first.attributes('aria-selected')).toBe('true')
    const second = wrapper.find('[data-test-id="base-tab-b"]')
    expect(second.attributes('aria-selected')).toBe('false')
  })

  it('emits update:modelValue on click', async () => {
    const wrapper = mount(BaseTabs, { props: { tabs, modelValue: 'a' } })
    await wrapper.find('[data-test-id="base-tab-b"]').trigger('click')
    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual(['b'])
  })

  it('activates next tab on ArrowRight', async () => {
    const wrapper = mount(BaseTabs, { props: { tabs, modelValue: 'a' } })
    await wrapper.find('[role="tablist"]').trigger('keydown', { key: 'ArrowRight' })
    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual(['b'])
  })

  it('wraps around on ArrowLeft from first tab', async () => {
    const wrapper = mount(BaseTabs, { props: { tabs, modelValue: 'a' } })
    await wrapper.find('[role="tablist"]').trigger('keydown', { key: 'ArrowLeft' })
    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual(['c'])
  })

  it('renders panel content via slot', () => {
    const wrapper = mount(BaseTabs, {
      props: { tabs, modelValue: 'a' },
      slots: { 'tab-a': 'Content A', 'tab-b': 'Content B' },
    })
    expect(wrapper.text()).toContain('Content A')
  })
})
