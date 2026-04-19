import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import BaseSelect from '../BaseSelect.vue'

describe('BaseSelect', () => {
  it('renders options and binds v-model', async () => {
    const wrapper = mount(BaseSelect, {
      props: {
        modelValue: 'a',
        options: [
          { value: 'a', label: 'Alpha' },
          { value: 'b', label: 'Beta' },
        ],
        id: 'x',
      },
    })
    const select = wrapper.find('select[data-test-id="base-select-x"]')
    expect(select.exists()).toBe(true)
    expect(wrapper.findAll('option')).toHaveLength(2)

    await select.setValue('b')
    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual(['b'])
  })

  it('renders placeholder option', () => {
    const wrapper = mount(BaseSelect, {
      props: {
        modelValue: '',
        placeholder: 'Choose',
        options: [{ value: '1', label: 'One' }],
        id: 'p',
      },
    })
    expect(wrapper.text()).toContain('Choose')
  })
})
