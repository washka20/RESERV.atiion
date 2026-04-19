import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import BaseStepper from '../BaseStepper.vue'

const steps = [
  { id: 'a', label: 'First' },
  { id: 'b', label: 'Second', description: 'Details' },
  { id: 'c', label: 'Third' },
]

describe('BaseStepper', () => {
  it('renders all steps with labels', () => {
    const wrapper = mount(BaseStepper, { props: { steps, modelValue: 'a' } })
    expect(wrapper.text()).toContain('First')
    expect(wrapper.text()).toContain('Second')
    expect(wrapper.text()).toContain('Third')
  })

  it('marks active step with aria-current', () => {
    const wrapper = mount(BaseStepper, { props: { steps, modelValue: 'b' } })
    const activeBtn = wrapper.find('[data-test-id="base-stepper-step-b"]')
    expect(activeBtn.attributes('aria-current')).toBe('step')
    const first = wrapper.find('[data-test-id="base-stepper-step-a"]')
    expect(first.attributes('aria-current')).toBeUndefined()
  })

  it('does not emit update on click when navigable=false', async () => {
    const wrapper = mount(BaseStepper, { props: { steps, modelValue: 'a' } })
    const btn = wrapper.find('[data-test-id="base-stepper-step-b"]')
    await btn.trigger('click')
    expect(wrapper.emitted('update:modelValue')).toBeUndefined()
  })

  it('emits update:modelValue on click when navigable=true', async () => {
    const wrapper = mount(BaseStepper, {
      props: { steps, modelValue: 'a', navigable: true },
    })
    await wrapper.find('[data-test-id="base-stepper-step-c"]').trigger('click')
    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual(['c'])
  })

  it('disables step buttons when not navigable', () => {
    const wrapper = mount(BaseStepper, { props: { steps, modelValue: 'a' } })
    const btn = wrapper.find('[data-test-id="base-stepper-step-b"]')
    expect(btn.attributes('disabled')).toBeDefined()
  })
})
