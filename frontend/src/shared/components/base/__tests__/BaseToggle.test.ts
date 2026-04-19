import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import BaseToggle from '../BaseToggle.vue'

describe('BaseToggle', () => {
  it('toggles on click', async () => {
    const wrapper = mount(BaseToggle, {
      props: { modelValue: false, label: 'Notifications', id: 'n' },
    })
    const btn = wrapper.find('[data-test-id="base-toggle-n"]')
    expect(btn.attributes('role')).toBe('switch')
    expect(btn.attributes('aria-checked')).toBe('false')
    await btn.trigger('click')
    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([true])
  })

  it('activates on Space keydown', async () => {
    const wrapper = mount(BaseToggle, {
      props: { modelValue: false, id: 'n' },
    })
    const btn = wrapper.find('[data-test-id="base-toggle-n"]')
    await btn.trigger('keydown', { key: ' ' })
    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([true])
  })

  it('does not toggle when disabled', async () => {
    const wrapper = mount(BaseToggle, {
      props: { modelValue: false, disabled: true, id: 'n' },
    })
    await wrapper.find('[data-test-id="base-toggle-n"]').trigger('click')
    expect(wrapper.emitted('update:modelValue')).toBeFalsy()
  })
})
