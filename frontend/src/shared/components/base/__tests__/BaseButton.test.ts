import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import BaseButton from '../BaseButton.vue'
import BaseSpinner from '../BaseSpinner.vue'

describe('BaseButton', () => {
  it('renders with default variant and correct data-test-id', () => {
    const wrapper = mount(BaseButton, { slots: { default: 'Hello' } })
    const btn = wrapper.find('[data-test-id="base-button-primary"]')
    expect(btn.exists()).toBe(true)
    expect(btn.text()).toContain('Hello')
  })

  it('emits click event when clicked', async () => {
    const wrapper = mount(BaseButton, { slots: { default: 'Go' } })
    await wrapper.find('button').trigger('click')
    expect(wrapper.emitted('click')).toBeTruthy()
    expect(wrapper.emitted('click')).toHaveLength(1)
  })

  it('does not emit click when disabled', async () => {
    const wrapper = mount(BaseButton, {
      props: { disabled: true },
      slots: { default: 'Go' },
    })
    await wrapper.find('button').trigger('click')
    expect(wrapper.emitted('click')).toBeFalsy()
  })

  it('shows spinner and is disabled when loading', () => {
    const wrapper = mount(BaseButton, {
      props: { loading: true },
      slots: { default: 'Wait' },
    })
    expect(wrapper.findComponent(BaseSpinner).exists()).toBe(true)
    const btn = wrapper.find('button')
    expect(btn.attributes('disabled')).toBeDefined()
    expect(btn.attributes('aria-busy')).toBe('true')
  })

  it('applies danger variant test-id', () => {
    const wrapper = mount(BaseButton, {
      props: { variant: 'danger' },
      slots: { default: 'Delete' },
    })
    expect(wrapper.find('[data-test-id="base-button-danger"]').exists()).toBe(true)
  })
})
