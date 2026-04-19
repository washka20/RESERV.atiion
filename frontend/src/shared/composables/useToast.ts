/**
 * Глобальный toast-queue. useToast() возвращает API для push/remove.
 * Состояние (reactive массив) общее на весь app; потребляется BaseToast.
 */
import { reactive, readonly } from 'vue'

export type ToastVariant = 'success' | 'error' | 'info' | 'warning'

export interface ToastOptions {
  duration?: number
}

export interface ToastItem {
  id: number
  message: string
  variant: ToastVariant
  duration: number
}

const DEFAULT_DURATION = 5000

interface ToastState {
  items: ToastItem[]
}

const state = reactive<ToastState>({ items: [] })
const timers = new Map<number, ReturnType<typeof setTimeout>>()
let nextId = 1

/** Внутренний pusher — используется публичными методами. */
function push(message: string, variant: ToastVariant, options?: ToastOptions): number {
  const id = nextId
  nextId += 1
  const duration = options?.duration ?? DEFAULT_DURATION
  state.items.push({ id, message, variant, duration })
  if (duration > 0) {
    const handle = setTimeout(() => {
      remove(id)
    }, duration)
    timers.set(id, handle)
  }
  return id
}

/** Удаляет toast по id и очищает таймер. */
export function remove(id: number): void {
  const idx = state.items.findIndex((t) => t.id === id)
  if (idx !== -1) state.items.splice(idx, 1)
  const handle = timers.get(id)
  if (handle) {
    clearTimeout(handle)
    timers.delete(id)
  }
}

/** Composable API для pushing toast-ов с готовыми variant-ами. */
export function useToast() {
  return {
    toast: {
      success: (msg: string, opts?: ToastOptions) => push(msg, 'success', opts),
      error: (msg: string, opts?: ToastOptions) => push(msg, 'error', opts),
      info: (msg: string, opts?: ToastOptions) => push(msg, 'info', opts),
      warning: (msg: string, opts?: ToastOptions) => push(msg, 'warning', opts),
      remove,
    },
    items: readonly(state.items),
  }
}

/** Доступ к reactive списку для BaseToast-контейнера. */
export function useToastItems(): Readonly<ToastItem[]> {
  return state.items
}
