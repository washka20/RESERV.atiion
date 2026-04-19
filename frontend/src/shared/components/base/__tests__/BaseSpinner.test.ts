import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import BaseSpinner from '../BaseSpinner.vue'

describe('BaseSpinner', () => {
  it('renders with role=status and default label', () => {
    const wrapper = mount(BaseSpinner)
    const el = wrapper.find('[data-test-id="base-spinner"]')
    expect(el.exists()).toBe(true)
    expect(el.attributes('role')).toBe('status')
    expect(el.attributes('aria-label')).toBe('Загрузка')
  })

  it('applies size-specific classes', () => {
    const wrapper = mount(BaseSpinner, { props: { size: 'lg' } })
    const el = wrapper.find('[data-test-id="base-spinner"]')
    expect(el.classes().join(' ')).toMatch(/w-8/)
  })
})
