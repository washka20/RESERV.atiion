import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import BaseCard from '../BaseCard.vue'

describe('BaseCard', () => {
  it('renders slot content with default padding and elevation', () => {
    const wrapper = mount(BaseCard, { slots: { default: 'Inside' } })
    const el = wrapper.find('[data-test-id="base-card"]')
    expect(el.exists()).toBe(true)
    expect(el.text()).toBe('Inside')
    expect(el.classes()).toContain('p-4')
    expect(el.classes()).toContain('shadow-sm')
  })

  it('applies lg padding and no shadow when elevation=none', () => {
    const wrapper = mount(BaseCard, {
      props: { padding: 'lg', elevation: 'none' },
      slots: { default: 'x' },
    })
    const classes = wrapper.find('[data-test-id="base-card"]').classes()
    expect(classes).toContain('p-6')
    expect(classes).not.toContain('shadow-sm')
  })
})
