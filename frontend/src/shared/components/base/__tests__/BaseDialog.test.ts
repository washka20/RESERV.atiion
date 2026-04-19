import { describe, it, expect, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import BaseDialog from '../BaseDialog.vue'

afterEach(() => {
  document.body.innerHTML = ''
})

describe('BaseDialog', () => {
  it('renders title and message', () => {
    mount(BaseDialog, {
      props: {
        modelValue: true,
        title: 'Delete?',
        message: 'Are you sure?',
      },
      attachTo: document.body,
    })
    const body = document.body.innerHTML
    expect(body).toContain('Delete?')
    expect(body).toContain('Are you sure?')
  })

  it('emits confirm on confirm button click', async () => {
    const wrapper = mount(BaseDialog, {
      props: { modelValue: true, title: 'T', message: 'm' },
      attachTo: document.body,
    })
    const btn = document.body.querySelector(
      '[data-test-id="base-dialog-confirm"]',
    ) as HTMLElement | null
    btn?.click()
    await wrapper.vm.$nextTick()
    expect(wrapper.emitted('confirm')).toBeTruthy()
  })

  it('emits cancel and closes on cancel click', async () => {
    const wrapper = mount(BaseDialog, {
      props: { modelValue: true, title: 'T', message: 'm' },
      attachTo: document.body,
    })
    const btn = document.body.querySelector(
      '[data-test-id="base-dialog-cancel"]',
    ) as HTMLElement | null
    btn?.click()
    await wrapper.vm.$nextTick()
    expect(wrapper.emitted('cancel')).toBeTruthy()
    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([false])
  })
})
