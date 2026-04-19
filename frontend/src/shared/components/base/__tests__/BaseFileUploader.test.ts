import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import BaseFileUploader from '../BaseFileUploader.vue'

const makeFile = (name: string, sizeBytes: number): File => {
  const f = new File(['x'.repeat(sizeBytes)], name, { type: 'text/plain' })
  Object.defineProperty(f, 'size', { value: sizeBytes })
  return f
}

describe('BaseFileUploader', () => {
  it('renders zone and no list initially', () => {
    const wrapper = mount(BaseFileUploader)
    expect(wrapper.find('[data-test-id="base-file-uploader-zone"]').exists()).toBe(true)
    expect(wrapper.find('[data-test-id="base-file-uploader-list"]').exists()).toBe(false)
  })

  it('renders preview list of files', () => {
    const f1 = makeFile('a.txt', 500)
    const f2 = makeFile('b.txt', 2048)
    const wrapper = mount(BaseFileUploader, {
      props: { modelValue: [f1, f2], multiple: true },
    })
    const list = wrapper.find('[data-test-id="base-file-uploader-list"]')
    expect(list.exists()).toBe(true)
    const items = wrapper.findAll('[data-test-id="base-file-uploader-item-name"]')
    expect(items.length).toBe(2)
    expect(items[0]?.text()).toBe('a.txt')
  })

  it('emits update:modelValue with files on drop', async () => {
    const wrapper = mount(BaseFileUploader, { props: { multiple: true } })
    const f = makeFile('drop.txt', 100)
    const zone = wrapper.find('[data-test-id="base-file-uploader-zone"]')
    await zone.trigger('drop', {
      dataTransfer: { files: [f] },
    })
    const emitted = wrapper.emitted('update:modelValue')
    expect(emitted).toBeDefined()
    const files = emitted?.[0]?.[0] as File[]
    expect(files.length).toBe(1)
    expect(files[0]?.name).toBe('drop.txt')
  })

  it('removes file on remove button click', async () => {
    const f1 = makeFile('a.txt', 100)
    const f2 = makeFile('b.txt', 100)
    const wrapper = mount(BaseFileUploader, {
      props: { modelValue: [f1, f2], multiple: true },
    })
    await wrapper.find('[data-test-id="base-file-uploader-remove-0"]').trigger('click')
    const emitted = wrapper.emitted('update:modelValue')
    expect(emitted).toBeDefined()
    const result = emitted?.[0]?.[0] as File[]
    expect(result.length).toBe(1)
    expect(result[0]?.name).toBe('b.txt')
  })

  it('emits error when file exceeds maxSizeMb', async () => {
    const wrapper = mount(BaseFileUploader, {
      props: { maxSizeMb: 1, multiple: true },
    })
    const bigFile = makeFile('big.bin', 2 * 1024 * 1024)
    await wrapper
      .find('[data-test-id="base-file-uploader-zone"]')
      .trigger('drop', { dataTransfer: { files: [bigFile] } })
    const errors = wrapper.emitted('error')
    expect(errors).toBeDefined()
    expect(errors?.[0]?.[0]).toContain('big.bin')
    const updates = wrapper.emitted('update:modelValue')
    const list = updates?.[0]?.[0] as File[]
    expect(list.length).toBe(0)
  })
})
