import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import BaseDataTable from '../BaseDataTable.vue'

const columns = [
  { key: 'name', label: 'Name', sortable: true },
  { key: 'email', label: 'Email' },
  { key: 'age', label: 'Age', align: 'right' as const, sortable: true },
]

const rows = [
  { name: 'Alice', email: 'a@x.com', age: 30 },
  { name: 'Bob', email: 'b@x.com', age: 25 },
]

describe('BaseDataTable', () => {
  it('renders columns and rows', () => {
    const wrapper = mount(BaseDataTable, { props: { columns, rows } })
    expect(wrapper.text()).toContain('Alice')
    expect(wrapper.text()).toContain('Bob')
    expect(wrapper.text()).toContain('a@x.com')
  })

  it('shows empty state when rows empty', () => {
    const wrapper = mount(BaseDataTable, {
      props: { columns, rows: [], emptyMessage: 'Nothing here' },
    })
    const empty = wrapper.find('[data-test-id="base-data-table-empty"]')
    expect(empty.exists()).toBe(true)
    expect(empty.text()).toBe('Nothing here')
  })

  it('emits sort with asc on first click', async () => {
    const wrapper = mount(BaseDataTable, { props: { columns, rows } })
    await wrapper.find('[data-test-id="base-data-table-sort-name"]').trigger('click')
    expect(wrapper.emitted('sort')?.[0]).toEqual(['name', 'asc'])
  })

  it('emits desc when clicking already asc-sorted column', async () => {
    const wrapper = mount(BaseDataTable, {
      props: { columns, rows, sortKey: 'name', sortDir: 'asc' },
    })
    await wrapper.find('[data-test-id="base-data-table-sort-name"]').trigger('click')
    expect(wrapper.emitted('sort')?.[0]).toEqual(['name', 'desc'])
  })

  it('does not emit sort for non-sortable column', async () => {
    const wrapper = mount(BaseDataTable, { props: { columns, rows } })
    expect(wrapper.find('[data-test-id="base-data-table-sort-email"]').exists()).toBe(false)
  })

  it('emits row-click on row click', async () => {
    const wrapper = mount(BaseDataTable, { props: { columns, rows } })
    await wrapper.find('[data-test-id="base-data-table-row-0"]').trigger('click')
    expect(wrapper.emitted('row-click')?.[0]?.[0]).toEqual(rows[0])
  })

  it('renders row-actions slot', () => {
    const wrapper = mount(BaseDataTable, {
      props: { columns, rows },
      slots: {
        'row-actions': '<button class="act-btn">Act</button>',
      },
    })
    expect(wrapper.findAll('.act-btn').length).toBe(2)
  })
})
