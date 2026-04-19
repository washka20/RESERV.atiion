import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import BaseInput from '../BaseInput.vue'

describe('BaseInput', () => {
  it('renders label and binds v-model', async () => {
    const wrapper = mount(BaseInput, {
      props: { modelValue: 'initial', label: 'Name', id: 'name' },
    })
    const input = wrapper.find('input[data-test-id="base-input-name"]')
    expect(input.exists()).toBe(true)
    expect(wrapper.find('label').text()).toContain('Name')
    expect((input.element as HTMLInputElement).value).toBe('initial')

    await input.setValue('updated')
    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual(['updated'])
  })

  it('shows error message and aria-invalid when error is set', () => {
    const wrapper = mount(BaseInput, {
      props: { modelValue: '', error: 'Required', id: 'x' },
    })
    expect(wrapper.text()).toContain('Required')
    const input = wrapper.find('input')
    expect(input.attributes('aria-invalid')).toBe('true')
  })

  it('shows helper text when no error', () => {
    const wrapper = mount(BaseInput, {
      props: { modelValue: '', helper: 'Use your real name', id: 'x' },
    })
    expect(wrapper.text()).toContain('Use your real name')
  })

  it('emits focus event', async () => {
    const wrapper = mount(BaseInput, { props: { modelValue: '', id: 'x' } })
    await wrapper.find('input').trigger('focus')
    expect(wrapper.emitted('focus')).toBeTruthy()
  })
})
