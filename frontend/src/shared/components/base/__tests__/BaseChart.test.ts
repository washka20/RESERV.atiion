import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import BaseChart from '../BaseChart.vue'

const data = [
  { x: 'Mon', y: 10 },
  { x: 'Tue', y: 20 },
  { x: 'Wed', y: 15 },
  { x: 'Thu', y: 30 },
]

describe('BaseChart', () => {
  it('renders SVG with correct type data-test-id', () => {
    const wrapper = mount(BaseChart, { props: { type: 'bar', data } })
    expect(wrapper.find('[data-test-id="base-chart"]').exists()).toBe(true)
    expect(wrapper.find('[data-test-id="base-chart-svg-bar"]').exists()).toBe(true)
  })

  it('renders bar count equal to data length for bar type', () => {
    const wrapper = mount(BaseChart, { props: { type: 'bar', data } })
    expect(wrapper.findAll('[data-test-id="base-chart-bar"]').length).toBe(data.length)
  })

  it('renders line path and points for line type', () => {
    const wrapper = mount(BaseChart, { props: { type: 'line', data } })
    expect(wrapper.find('[data-test-id="base-chart-line"]').exists()).toBe(true)
    expect(wrapper.findAll('[data-test-id="base-chart-point"]').length).toBe(data.length)
  })

  it('renders label in figcaption', () => {
    const wrapper = mount(BaseChart, {
      props: { type: 'area', data, label: 'Bookings' },
    })
    expect(wrapper.text()).toContain('Bookings')
  })
})
