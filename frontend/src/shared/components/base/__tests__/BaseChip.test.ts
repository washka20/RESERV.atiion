import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import BaseChip from '../BaseChip.vue'

describe('BaseChip', () => {
  it('renders label', () => {
    const wrapper = mount(BaseChip, { props: { label: 'Filter' } })
    expect(wrapper.find('[data-test-id="base-chip"]').text()).toContain('Filter')
  })

  it('emits click when clickable and clicked', async () => {
    const wrapper = mount(BaseChip, { props: { label: 'Tag', clickable: true } })
    await wrapper.find('[data-test-id="base-chip"]').trigger('click')
    expect(wrapper.emitted('click')).toBeTruthy()
  })

  it('emits click on Enter key when clickable', async () => {
    const wrapper = mount(BaseChip, { props: { label: 'Tag', clickable: true } })
    await wrapper.find('[data-test-id="base-chip"]').trigger('keydown', { key: 'Enter' })
    expect(wrapper.emitted('click')).toBeTruthy()
  })

  it('emits remove on Delete key when removable', async () => {
    const wrapper = mount(BaseChip, { props: { label: 'Tag', removable: true } })
    await wrapper.find('[data-test-id="base-chip"]').trigger('keydown', { key: 'Delete' })
    expect(wrapper.emitted('remove')).toBeTruthy()
  })

  it('emits remove when remove button clicked', async () => {
    const wrapper = mount(BaseChip, { props: { label: 'Tag', removable: true } })
    await wrapper.find('[data-test-id="base-chip-remove"]').trigger('click')
    expect(wrapper.emitted('remove')).toBeTruthy()
  })
})
