import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import BaseTextarea from '../BaseTextarea.vue'

describe('BaseTextarea', () => {
  it('renders with rows=4 by default and binds v-model', async () => {
    const wrapper = mount(BaseTextarea, {
      props: { modelValue: 'hello', id: 'msg' },
    })
    const ta = wrapper.find('textarea[data-test-id="base-textarea-msg"]')
    expect(ta.exists()).toBe(true)
    expect(ta.attributes('rows')).toBe('4')
    expect((ta.element as HTMLTextAreaElement).value).toBe('hello')

    await ta.setValue('world')
    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual(['world'])
  })

  it('shows error when provided', () => {
    const wrapper = mount(BaseTextarea, {
      props: { modelValue: '', error: 'Too short', id: 'msg' },
    })
    expect(wrapper.text()).toContain('Too short')
    expect(wrapper.find('textarea').attributes('aria-invalid')).toBe('true')
  })
})
