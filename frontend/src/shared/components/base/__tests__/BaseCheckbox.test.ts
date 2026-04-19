import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import BaseCheckbox from '../BaseCheckbox.vue'

describe('BaseCheckbox', () => {
  it('renders and toggles on change', async () => {
    const wrapper = mount(BaseCheckbox, {
      props: { modelValue: false, label: 'Agree', id: 'tos' },
    })
    const input = wrapper.find('input[data-test-id="base-checkbox-tos"]')
    expect(input.exists()).toBe(true)
    expect(wrapper.text()).toContain('Agree')

    await input.setValue(true)
    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([true])
  })

  it('reflects aria-checked prop', () => {
    const wrapper = mount(BaseCheckbox, {
      props: { modelValue: true, id: 'x' },
    })
    expect(wrapper.find('input').attributes('aria-checked')).toBe('true')
  })
})
