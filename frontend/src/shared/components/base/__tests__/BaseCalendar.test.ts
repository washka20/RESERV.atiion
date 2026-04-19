import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import BaseCalendar from '../BaseCalendar.vue'

describe('BaseCalendar', () => {
  it('renders grid with weekdays and title', () => {
    const value = new Date(2026, 3, 15)
    const wrapper = mount(BaseCalendar, { props: { modelValue: value } })
    expect(wrapper.find('[data-test-id="base-calendar"]').exists()).toBe(true)
    expect(wrapper.find('[data-test-id="base-calendar-title"]').text()).toContain('2026')
    expect(wrapper.findAll('[role="columnheader"]').length).toBe(7)
  })

  it('emits update:modelValue on day click', async () => {
    const value = new Date(2026, 3, 15)
    const wrapper = mount(BaseCalendar, { props: { modelValue: value } })
    await wrapper
      .find('[data-test-id="base-calendar-day-2026-3-20"]')
      .trigger('click')
    const events = wrapper.emitted('update:modelValue')
    expect(events).toBeDefined()
    const emitted = events?.[0]?.[0] as Date
    expect(emitted.getFullYear()).toBe(2026)
    expect(emitted.getMonth()).toBe(3)
    expect(emitted.getDate()).toBe(20)
  })

  it('moves focused day on ArrowRight', async () => {
    const value = new Date(2026, 3, 15)
    const wrapper = mount(BaseCalendar, {
      props: { modelValue: value },
      attachTo: document.body,
    })
    const grid = wrapper.find('[role="grid"]')
    await grid.trigger('keydown', { key: 'ArrowRight' })
    const btn = wrapper.find('[data-test-id="base-calendar-day-2026-3-16"]')
    expect(btn.attributes('tabindex')).toBe('0')
    wrapper.unmount()
  })

  it('switches month on prev/next buttons', async () => {
    const value = new Date(2026, 3, 15)
    const wrapper = mount(BaseCalendar, { props: { modelValue: value } })
    await wrapper.find('[data-test-id="base-calendar-next"]').trigger('click')
    expect(wrapper.find('[data-test-id="base-calendar-title"]').text()).toContain('Май')
    await wrapper.find('[data-test-id="base-calendar-prev"]').trigger('click')
    await wrapper.find('[data-test-id="base-calendar-prev"]').trigger('click')
    expect(wrapper.find('[data-test-id="base-calendar-title"]').text()).toContain('Март')
  })

  it('disables dates outside min/max', () => {
    const value = new Date(2026, 3, 15)
    const wrapper = mount(BaseCalendar, {
      props: {
        modelValue: value,
        min: new Date(2026, 3, 10),
        max: new Date(2026, 3, 20),
      },
    })
    const outside = wrapper.find('[data-test-id="base-calendar-day-2026-3-5"]')
    expect(outside.attributes('disabled')).toBeDefined()
  })
})
