import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import BasePagination from '../BasePagination.vue'

describe('BasePagination', () => {
  it('emits update:currentPage on page click', async () => {
    const wrapper = mount(BasePagination, {
      props: { currentPage: 3, totalPages: 5 },
    })
    await wrapper.find('[data-test-id="base-pagination-page-4"]').trigger('click')
    expect(wrapper.emitted('update:currentPage')?.[0]).toEqual([4])
  })

  it('disables prev on page 1', () => {
    const wrapper = mount(BasePagination, {
      props: { currentPage: 1, totalPages: 5 },
    })
    expect(wrapper.find('[data-test-id="base-pagination-prev"]').attributes('disabled')).toBeDefined()
  })

  it('disables next on last page', () => {
    const wrapper = mount(BasePagination, {
      props: { currentPage: 5, totalPages: 5 },
    })
    expect(wrapper.find('[data-test-id="base-pagination-next"]').attributes('disabled')).toBeDefined()
  })

  it('renders ellipsis for large ranges', () => {
    const wrapper = mount(BasePagination, {
      props: { currentPage: 6, totalPages: 42 },
    })
    const text = wrapper.text()
    expect(text).toContain('…')
    expect(text).toContain('1')
    expect(text).toContain('42')
  })

  it('marks current page with aria-current=page', () => {
    const wrapper = mount(BasePagination, {
      props: { currentPage: 2, totalPages: 5 },
    })
    const current = wrapper.find('[data-test-id="base-pagination-page-2"]')
    expect(current.attributes('aria-current')).toBe('page')
  })
})
