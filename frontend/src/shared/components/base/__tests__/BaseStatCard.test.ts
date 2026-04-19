import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import BaseStatCard from '../BaseStatCard.vue'

describe('BaseStatCard', () => {
  it('renders label, value and hint', () => {
    const wrapper = mount(BaseStatCard, {
      props: { label: 'Revenue', value: '$1,200', hint: 'vs last week' },
    })
    expect(wrapper.find('[data-test-id="base-stat-card-label"]').text()).toBe('Revenue')
    expect(wrapper.find('[data-test-id="base-stat-card-value"]').text()).toBe('$1,200')
    expect(wrapper.find('[data-test-id="base-stat-card-hint"]').text()).toBe('vs last week')
  })

  it('does not render delta when missing', () => {
    const wrapper = mount(BaseStatCard, {
      props: { label: 'L', value: 10 },
    })
    expect(wrapper.find('[data-test-id="base-stat-card-delta"]').exists()).toBe(false)
  })

  it('applies success color for up trend', () => {
    const wrapper = mount(BaseStatCard, {
      props: { label: 'L', value: 10, delta: '+12%', trend: 'up' },
    })
    const delta = wrapper.find('[data-test-id="base-stat-card-delta"]')
    expect(delta.exists()).toBe(true)
    expect(delta.classes()).toContain('text-success')
  })

  it('applies danger color for down trend', () => {
    const wrapper = mount(BaseStatCard, {
      props: { label: 'L', value: 10, delta: '-5%', trend: 'down' },
    })
    expect(wrapper.find('[data-test-id="base-stat-card-delta"]').classes()).toContain(
      'text-danger',
    )
  })

  it('applies muted color for flat trend', () => {
    const wrapper = mount(BaseStatCard, {
      props: { label: 'L', value: 10, delta: '0%', trend: 'flat' },
    })
    expect(wrapper.find('[data-test-id="base-stat-card-delta"]').classes()).toContain(
      'text-text-subtle',
    )
  })
})
