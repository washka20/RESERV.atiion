import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import BaseBadge from '../BaseBadge.vue'

describe('BaseBadge', () => {
  it('renders slot content with default neutral variant', () => {
    const wrapper = mount(BaseBadge, { slots: { default: 'NEW' } })
    const el = wrapper.find('[data-test-id="base-badge-neutral"]')
    expect(el.exists()).toBe(true)
    expect(el.text()).toBe('NEW')
  })

  it('applies variant-specific test-id', () => {
    const wrapper = mount(BaseBadge, {
      props: { variant: 'danger' },
      slots: { default: 'ERR' },
    })
    expect(wrapper.find('[data-test-id="base-badge-danger"]').exists()).toBe(true)
  })
})
