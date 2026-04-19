import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import BaseEmptyState from '../BaseEmptyState.vue'

describe('BaseEmptyState', () => {
  it('renders title and description', () => {
    const wrapper = mount(BaseEmptyState, {
      props: { title: 'No results', description: 'Try another search' },
    })
    expect(wrapper.find('[data-test-id="base-empty-state"]').exists()).toBe(true)
    expect(wrapper.text()).toContain('No results')
    expect(wrapper.text()).toContain('Try another search')
  })

  it('renders slot content for icon and action', () => {
    const wrapper = mount(BaseEmptyState, {
      props: { title: 'Empty' },
      slots: {
        icon: '<span data-test-id="icon-x">ICN</span>',
        action: '<button data-test-id="action-x">Do it</button>',
      },
    })
    expect(wrapper.find('[data-test-id="icon-x"]').exists()).toBe(true)
    expect(wrapper.find('[data-test-id="action-x"]').exists()).toBe(true)
  })
})
