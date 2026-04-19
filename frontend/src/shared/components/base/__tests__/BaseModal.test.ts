import { describe, it, expect, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import BaseModal from '../BaseModal.vue'

const findBody = (selector: string) => document.body.querySelector(selector)

afterEach(() => {
  document.body.innerHTML = ''
  document.body.style.overflow = ''
})

describe('BaseModal', () => {
  it('renders with role=dialog when modelValue=true', () => {
    mount(BaseModal, {
      props: { modelValue: true, title: 'Confirm' },
      slots: { default: 'Body' },
      attachTo: document.body,
    })
    const modal = findBody('[data-test-id="base-modal"]')
    expect(modal).toBeTruthy()
    expect(modal?.getAttribute('role')).toBe('dialog')
    expect(modal?.getAttribute('aria-modal')).toBe('true')
  })

  it('does not render when modelValue=false', () => {
    mount(BaseModal, {
      props: { modelValue: false },
      attachTo: document.body,
    })
    expect(findBody('[data-test-id="base-modal"]')).toBeNull()
  })

  it('emits update:modelValue=false on close button click', async () => {
    const wrapper = mount(BaseModal, {
      props: { modelValue: true, title: 'T' },
      attachTo: document.body,
    })
    const btn = document.body.querySelector(
      '[data-test-id="base-modal-close"]',
    ) as HTMLElement | null
    btn?.click()
    await wrapper.vm.$nextTick()
    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([false])
  })

  it('emits update:modelValue=false on Escape key', async () => {
    const wrapper = mount(BaseModal, {
      props: { modelValue: true, title: 'T' },
      attachTo: document.body,
    })
    const modal = document.body.querySelector(
      '[data-test-id="base-modal"]',
    ) as HTMLElement
    modal.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }))
    await wrapper.vm.$nextTick()
    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([false])
  })

  it('closes on backdrop click when dismissible', async () => {
    const wrapper = mount(BaseModal, {
      props: { modelValue: true, title: 'T' },
      attachTo: document.body,
    })
    const backdrop = document.body.querySelector(
      '[data-test-id="base-modal-backdrop"]',
    ) as HTMLElement
    backdrop.dispatchEvent(new MouseEvent('click', { bubbles: true }))
    await wrapper.vm.$nextTick()
    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([false])
  })
})
