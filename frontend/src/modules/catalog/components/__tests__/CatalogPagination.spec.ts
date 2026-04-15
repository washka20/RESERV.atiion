import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import Pagination from '../CatalogPagination.vue'
import type { PaginationMeta } from '@/types/catalog.types'

function meta(page: number, lastPage: number): PaginationMeta {
  return { total: lastPage * 20, page, perPage: 20, lastPage }
}

describe('Pagination', () => {
  it('disables Prev on first page', () => {
    const wrapper = mount(Pagination, { props: { meta: meta(1, 5) } })
    const prev = wrapper.get('[data-test-id="catalog-pagination-prev"]')
    const next = wrapper.get('[data-test-id="catalog-pagination-next"]')
    expect((prev.element as HTMLButtonElement).disabled).toBe(true)
    expect((next.element as HTMLButtonElement).disabled).toBe(false)
  })

  it('disables Next on last page', () => {
    const wrapper = mount(Pagination, { props: { meta: meta(5, 5) } })
    const prev = wrapper.get('[data-test-id="catalog-pagination-prev"]')
    const next = wrapper.get('[data-test-id="catalog-pagination-next"]')
    expect((prev.element as HTMLButtonElement).disabled).toBe(false)
    expect((next.element as HTMLButtonElement).disabled).toBe(true)
  })

  it('emits change with previous page on Prev click', async () => {
    const wrapper = mount(Pagination, { props: { meta: meta(3, 5) } })
    await wrapper.get('[data-test-id="catalog-pagination-prev"]').trigger('click')
    expect(wrapper.emitted('change')).toEqual([[2]])
  })

  it('emits change with next page on Next click', async () => {
    const wrapper = mount(Pagination, { props: { meta: meta(3, 5) } })
    await wrapper.get('[data-test-id="catalog-pagination-next"]').trigger('click')
    expect(wrapper.emitted('change')).toEqual([[4]])
  })

  it('does not emit when click on disabled button', async () => {
    const wrapper = mount(Pagination, { props: { meta: meta(1, 5) } })
    await wrapper.get('[data-test-id="catalog-pagination-prev"]').trigger('click')
    expect(wrapper.emitted('change')).toBeUndefined()
  })

  it('renders current and last page info', () => {
    const wrapper = mount(Pagination, { props: { meta: meta(2, 7) } })
    expect(wrapper.text()).toContain('Стр. 2 из 7')
  })
})
