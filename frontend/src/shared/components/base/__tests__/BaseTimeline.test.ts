import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import BaseTimeline from '../BaseTimeline.vue'

const events = [
  { id: 1, date: '2026-01-01', title: 'Created', variant: 'success' as const },
  {
    id: 2,
    date: '2026-01-02',
    title: 'Updated',
    description: 'Some info',
    variant: 'warning' as const,
  },
  { id: 3, date: '2026-01-03', title: 'Removed', variant: 'danger' as const },
]

describe('BaseTimeline', () => {
  it('renders events count', () => {
    const wrapper = mount(BaseTimeline, { props: { events } })
    expect(wrapper.findAll('[data-test-id^="base-timeline-item-"]').length).toBe(
      events.length,
    )
  })

  it('renders title, date and description', () => {
    const wrapper = mount(BaseTimeline, { props: { events } })
    expect(wrapper.text()).toContain('Created')
    expect(wrapper.text()).toContain('2026-01-01')
    expect(wrapper.text()).toContain('Some info')
  })

  it('applies variant-specific dot color', () => {
    const wrapper = mount(BaseTimeline, { props: { events } })
    expect(wrapper.find('[data-test-id="base-timeline-dot-success"]').exists()).toBe(
      true,
    )
    expect(wrapper.find('[data-test-id="base-timeline-dot-warning"]').exists()).toBe(
      true,
    )
    expect(wrapper.find('[data-test-id="base-timeline-dot-danger"]').exists()).toBe(
      true,
    )
    expect(
      wrapper.find('[data-test-id="base-timeline-dot-success"]').classes(),
    ).toContain('bg-success')
  })

  it('defaults to neutral variant when not provided', () => {
    const wrapper = mount(BaseTimeline, {
      props: { events: [{ id: 'x', date: 'd', title: 't' }] },
    })
    expect(wrapper.find('[data-test-id="base-timeline-dot-neutral"]').exists()).toBe(
      true,
    )
  })
})
