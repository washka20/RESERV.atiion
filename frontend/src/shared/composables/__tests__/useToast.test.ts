import { describe, it, expect, beforeEach, vi } from 'vitest'
import { useToast, useToastItems, remove } from '../useToast'

describe('useToast', () => {
  beforeEach(() => {
    const items = useToastItems()
    while (items.length > 0) {
      remove(items[0]!.id)
    }
    vi.useRealTimers()
  })

  it('pushes success toast and appears in queue', () => {
    const { toast } = useToast()
    const items = useToastItems()
    expect(items.length).toBe(0)
    toast.success('Done', { duration: 0 })
    expect(items.length).toBe(1)
    expect(items[0]?.variant).toBe('success')
    expect(items[0]?.message).toBe('Done')
  })

  it('auto-removes toast after duration', () => {
    vi.useFakeTimers()
    const { toast } = useToast()
    const items = useToastItems()
    toast.error('Oops', { duration: 1000 })
    expect(items.length).toBe(1)
    vi.advanceTimersByTime(1100)
    expect(items.length).toBe(0)
  })

  it('remove by id clears immediately', () => {
    const { toast } = useToast()
    const items = useToastItems()
    const id = toast.info('hey', { duration: 0 })
    expect(items.length).toBe(1)
    remove(id)
    expect(items.length).toBe(0)
  })

  it('supports multiple toasts in queue', () => {
    const { toast } = useToast()
    const items = useToastItems()
    toast.success('a', { duration: 0 })
    toast.warning('b', { duration: 0 })
    toast.error('c', { duration: 0 })
    expect(items.length).toBe(3)
    expect(items.map((i) => i.variant)).toEqual(['success', 'warning', 'error'])
  })
})
