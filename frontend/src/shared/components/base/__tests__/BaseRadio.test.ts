import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import BaseRadio from '../BaseRadio.vue'

describe('BaseRadio', () => {
  it('emits value when changed', async () => {
    const wrapper = mount(BaseRadio, {
      props: { modelValue: 'a', value: 'b', name: 'g', label: 'B', id: 'rb' },
    })
    const input = wrapper.find('input[data-test-id="base-radio-rb"]')
    await input.trigger('change')
    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual(['b'])
  })

  it('shows checked state when modelValue matches value', () => {
    const wrapper = mount(BaseRadio, {
      props: { modelValue: 'x', value: 'x', name: 'g', id: 'rx' },
    })
    expect(wrapper.find('input').attributes('aria-checked')).toBe('true')
  })
})
