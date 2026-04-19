import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import BaseSkeleton from '../BaseSkeleton.vue'

describe('BaseSkeleton', () => {
  it('renders N lines for text variant', () => {
    const wrapper = mount(BaseSkeleton, { props: { variant: 'text', lines: 5 } })
    expect(wrapper.findAll('[data-test-id="base-skeleton-line"]')).toHaveLength(5)
  })

  it('renders single block for card variant', () => {
    const wrapper = mount(BaseSkeleton, { props: { variant: 'card' } })
    expect(wrapper.find('[data-test-id="base-skeleton"]').exists()).toBe(true)
    expect(wrapper.findAll('[data-test-id="base-skeleton-line"]')).toHaveLength(0)
  })

  it('applies custom width/height for custom variant', () => {
    const wrapper = mount(BaseSkeleton, {
      props: { variant: 'custom', width: '120px', height: '20px' },
    })
    const el = wrapper.find('[data-test-id="base-skeleton"]')
    const style = el.attributes('style') ?? ''
    expect(style).toContain('width: 120px')
    expect(style).toContain('height: 20px')
  })
})
