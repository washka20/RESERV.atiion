import { type Ref, onBeforeUnmount, onMounted, watch } from 'vue'

/**
 * Focus trap: при активации фокусирует первый focusable внутри root,
 * ловит Tab/Shift+Tab и циклически переводит фокус внутри контейнера.
 * При деактивации возвращает фокус на элемент, который был сфокусирован до активации.
 */
const FOCUSABLE_SELECTOR = [
  'a[href]',
  'button:not([disabled])',
  'textarea:not([disabled])',
  'input:not([disabled])',
  'select:not([disabled])',
  '[tabindex]:not([tabindex="-1"])',
].join(',')

/**
 * @param rootRef — ref на корневой HTMLElement, внутри которого ловится фокус.
 * @param active — ref на состояние активации (true → trap работает).
 */
export function useFocusTrap(
  rootRef: Ref<HTMLElement | null>,
  active: Ref<boolean>,
): void {
  let previouslyFocused: HTMLElement | null = null

  const getFocusable = (): HTMLElement[] => {
    const root = rootRef.value
    if (!root) return []
    return Array.from(root.querySelectorAll<HTMLElement>(FOCUSABLE_SELECTOR))
      .filter((el) => !el.hasAttribute('disabled'))
  }

  const onKeyDown = (event: KeyboardEvent) => {
    if (!active.value || event.key !== 'Tab') return
    const focusable = getFocusable()
    if (!focusable.length) {
      event.preventDefault()
      return
    }
    const first = focusable[0]
    const last = focusable[focusable.length - 1]
    const activeEl = document.activeElement as HTMLElement | null

    if (event.shiftKey && activeEl === first) {
      event.preventDefault()
      last.focus()
    } else if (!event.shiftKey && activeEl === last) {
      event.preventDefault()
      first.focus()
    }
  }

  const activate = () => {
    previouslyFocused = document.activeElement as HTMLElement | null
    const focusable = getFocusable()
    if (focusable.length) focusable[0].focus()
    document.addEventListener('keydown', onKeyDown)
  }

  const deactivate = () => {
    document.removeEventListener('keydown', onKeyDown)
    previouslyFocused?.focus?.()
    previouslyFocused = null
  }

  watch(active, (val) => {
    if (val) {
      queueMicrotask(activate)
    } else {
      deactivate()
    }
  })

  onMounted(() => {
    if (active.value) queueMicrotask(activate)
  })

  onBeforeUnmount(() => {
    deactivate()
  })
}
