import { describe, it, expect, afterEach } from 'vitest'
import { defineComponent, h, ref } from 'vue'
import { mount } from '@vue/test-utils'
import { useFocusTrap } from '../useFocusTrap'

const TestHost = defineComponent({
  setup() {
    const rootRef = ref<HTMLElement | null>(null)
    const active = ref(true)
    useFocusTrap(rootRef, active)
    return { rootRef, active }
  },
  render() {
    return h('div', { ref: 'rootRef' }, [
      h('button', { id: 'a', 'data-test-id': 'a' }, 'A'),
      h('button', { id: 'b', 'data-test-id': 'b' }, 'B'),
      h('button', { id: 'c', 'data-test-id': 'c' }, 'C'),
    ])
  },
})

afterEach(() => {
  document.body.innerHTML = ''
})

describe('useFocusTrap', () => {
  it('focuses first focusable on mount', async () => {
    mount(TestHost, { attachTo: document.body })
    await new Promise((resolve) => queueMicrotask(() => resolve(null)))
    expect(document.activeElement?.id).toBe('a')
  })

  it('wraps focus: Tab on last element goes to first', async () => {
    mount(TestHost, { attachTo: document.body })
    await new Promise((resolve) => queueMicrotask(() => resolve(null)))
    const last = document.getElementById('c')!
    last.focus()
    expect(document.activeElement).toBe(last)

    const evt = new KeyboardEvent('keydown', { key: 'Tab', bubbles: true, cancelable: true })
    document.dispatchEvent(evt)
    expect(document.activeElement?.id).toBe('a')
  })
})
